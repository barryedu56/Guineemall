from django.db.models import Avg, Count, F, Q
from django.http import HttpResponse
from rest_framework import generics, mixins, parsers, permissions, status, viewsets
from rest_framework.decorators import action
from rest_framework.exceptions import ValidationError
from rest_framework.response import Response

from apps.core.permissions import IsVendeur, get_vendor
from apps.notifications.services import notify
from apps.orders.models import OrderItem, VendorOrder
from apps.vendors.models import Vendor

from . import stats
from .filters import ProductFilter
from .models import Category, Favorite, Product, Review
from .serializers import (
    CategorySerializer,
    ProductDetailSerializer,
    ProductImageSerializer,
    ProductListSerializer,
    ReviewSerializer,
    VendorProductSerializer,
)
from .share import build_share_image


def public_products():
    """Produits visibles par tous : actifs, non supprimés, d'une boutique approuvée."""
    visible = Q(reviews__is_visible=True)
    return (
        Product.objects.alive()
        .filter(
            status=Product.Status.ACTIVE,
            vendor__status=Vendor.Status.APPROVED,
            vendor__deleted_at__isnull=True,
        )
        .annotate(rating=Avg("reviews__rating", filter=visible), reviews_count=Count("reviews", filter=visible))
        .select_related("vendor", "category")
        .prefetch_related("images")
    )


class CategoryViewSet(viewsets.ReadOnlyModelViewSet):
    queryset = Category.objects.filter(status=Category.Status.ACTIVE).annotate(
        products_count=Count(
            "products",
            filter=Q(products__status=Product.Status.ACTIVE, products__deleted_at__isnull=True,
                     products__vendor__status=Vendor.Status.APPROVED),
        )
    )
    serializer_class = CategorySerializer
    lookup_field = "slug"
    pagination_class = None


class ProductViewSet(viewsets.ReadOnlyModelViewSet):
    lookup_field = "slug"
    filterset_class = ProductFilter
    search_fields = ["name", "description", "vendor__shop_name", "category__name"]
    ordering_fields = ["price", "created_at"]

    def get_queryset(self):
        return public_products()

    def get_serializer_class(self):
        return ProductDetailSerializer if self.action == "retrieve" else ProductListSerializer

    @action(detail=False, url_path="for-you", permission_classes=[permissions.IsAuthenticated])
    def for_you(self, request):
        """
        Sélection personnalisée : produits des catégories que le client achète ou met en favori,
        qu'il n'a pas encore achetés. À défaut, les produits les mieux notés puis les plus récents.
        """
        user = request.user
        bought = OrderItem.objects.filter(order__user=user, product__isnull=False).values_list("product_id", flat=True)
        categories = set(Product.objects.filter(pk__in=bought).values_list("category_id", flat=True))
        categories |= set(Product.objects.filter(favorited_by__user=user).values_list("category_id", flat=True))

        products = public_products().exclude(pk__in=bought).filter(stock__gt=0)
        if categories:
            products = products.filter(category_id__in=categories)
        products = list(products.order_by(F("rating").desc(nulls_last=True), "-created_at")[:10])
        if len(products) < 10:
            seen = [p.pk for p in products]
            products += list(
                public_products().exclude(pk__in=[*seen, *bought]).filter(stock__gt=0).order_by("-created_at")[: 10 - len(products)]
            )
        return Response(ProductListSerializer(products, many=True, context={"request": request}).data)

    def retrieve(self, request, *args, **kwargs):
        response = super().retrieve(request, *args, **kwargs)
        stats.record_view(response.data["id"])
        return response

    @action(detail=True, methods=["get", "post"], permission_classes=[permissions.IsAuthenticatedOrReadOnly],
            parser_classes=[parsers.MultiPartParser, parsers.FormParser, parsers.JSONParser])
    def reviews(self, request, slug=None):
        """GET : avis du produit · POST : laisser un avis (uniquement après l'avoir reçu)."""
        product = self.get_object()
        if request.method == "GET":
            reviews = product.reviews.filter(is_visible=True).select_related("user").prefetch_related("photos")
            page = self.paginate_queryset(reviews)
            return self.get_paginated_response(ReviewSerializer(page, many=True, context={"request": request}).data)

        bought = OrderItem.objects.filter(
            product=product, order__user=request.user, vendor_order__status=VendorOrder.Status.DELIVERED
        ).exists()
        if not bought:
            raise ValidationError({"detail": "Seuls les clients qui ont reçu ce produit peuvent laisser un avis."})
        if product.reviews.filter(user=request.user).exists():
            raise ValidationError({"detail": "Vous avez déjà laissé un avis sur ce produit."})
        serializer = ReviewSerializer(data=request.data, context={"request": request})
        serializer.is_valid(raise_exception=True)
        review = serializer.save(product=product, user=request.user)
        notify(product.vendor.user, "review", f"Nouvel avis {review.rating}★ sur « {product.name} »",
               review.comment[:140], f"/produit/{product.slug}", whatsapp=False)
        return Response(serializer.data, status=status.HTTP_201_CREATED)

    @action(detail=True, url_path="share-image")
    def share_image(self, request, slug=None):
        """Image carrée prête à poster en statut WhatsApp, sur Facebook ou TikTok."""
        product = self.get_object()
        return HttpResponse(build_share_image(product), content_type="image/jpeg")


class VendorReviewViewSet(mixins.ListModelMixin, viewsets.GenericViewSet):
    """Avis reçus par la boutique, avec possibilité de répondre."""

    serializer_class = ReviewSerializer
    permission_classes = [IsVendeur]

    def get_queryset(self):
        return Review.objects.filter(product__vendor=get_vendor(self.request)).select_related("user", "product")

    def list(self, request, *args, **kwargs):
        page = self.paginate_queryset(self.get_queryset())
        data = [
            {**ReviewSerializer(r, context={"request": request}).data, "product": {"slug": r.product.slug, "name": r.product.name}}
            for r in page
        ]
        return self.get_paginated_response(data)

    @action(detail=True, methods=["post"])
    def reply(self, request, pk=None):
        review = self.get_object()
        review.vendor_reply = str(request.data.get("reply", ""))[:1000]
        review.save(update_fields=["vendor_reply", "updated_at"])
        return Response(ReviewSerializer(review, context={"request": request}).data)


class VendorProductViewSet(viewsets.ModelViewSet):
    """Espace vendeur : gestion de ses propres produits."""

    serializer_class = VendorProductSerializer
    permission_classes = [IsVendeur]
    filterset_fields = ["status"]
    search_fields = ["name"]

    def get_queryset(self):
        return get_vendor(self.request).products.alive().prefetch_related("images")

    def perform_create(self, serializer):
        serializer.save(vendor=get_vendor(self.request))

    @action(detail=True, methods=["post"], parser_classes=[parsers.MultiPartParser])
    def images(self, request, pk=None):
        product = self.get_object()
        serializer = ProductImageSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        serializer.save(product=product)
        return Response(serializer.data, status=status.HTTP_201_CREATED)

    @action(detail=True, methods=["delete"], url_path=r"images/(?P<image_id>\d+)")
    def delete_image(self, request, pk=None, image_id=None):
        product = self.get_object()
        generics.get_object_or_404(product.images, pk=image_id).delete()
        return Response(status=status.HTTP_204_NO_CONTENT)


class FavoriteViewSet(mixins.ListModelMixin, viewsets.GenericViewSet):
    """GET /favorites/ · POST /favorites/<slug>/ · DELETE /favorites/<slug>/"""

    serializer_class = ProductListSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        return public_products().filter(favorited_by__user=self.request.user)

    @action(detail=False, methods=["post", "delete"], url_path=r"(?P<slug>[-\w]+)")
    def toggle(self, request, slug=None):
        product = generics.get_object_or_404(public_products(), slug=slug)
        if request.method == "POST":
            Favorite.objects.get_or_create(user=request.user, product=product)
            return Response(status=status.HTTP_201_CREATED)
        Favorite.objects.filter(user=request.user, product=product).delete()
        return Response(status=status.HTTP_204_NO_CONTENT)


class VendorStatsViewSet(viewsets.GenericViewSet):
    permission_classes = [IsVendeur]

    def list(self, request):
        days = min(int(request.query_params.get("days", 30)), 365)
        return Response(stats.vendor_dashboard(get_vendor(request), days))
