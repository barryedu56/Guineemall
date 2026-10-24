from rest_framework import generics, mixins, parsers, permissions, status, viewsets
from rest_framework.decorators import action
from rest_framework.response import Response

from apps.core.permissions import IsVendeur, get_vendor
from apps.vendors.models import Vendor

from .filters import ProductFilter
from .models import Category, Favorite, Product
from .serializers import (
    CategorySerializer,
    ProductDetailSerializer,
    ProductImageSerializer,
    ProductListSerializer,
    VendorProductSerializer,
)


def public_products():
    """Produits visibles par tous : actifs, non supprimés, d'une boutique approuvée."""
    return (
        Product.objects.alive()
        .filter(
            status=Product.Status.ACTIVE,
            vendor__status=Vendor.Status.APPROVED,
            vendor__deleted_at__isnull=True,
        )
        .select_related("vendor", "category")
        .prefetch_related("images")
    )


class CategoryViewSet(viewsets.ReadOnlyModelViewSet):
    queryset = Category.objects.filter(status=Category.Status.ACTIVE)
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
