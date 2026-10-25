"""
API du back-office (/api/backoffice/…) : réservée à l'équipe avec double authentification.
Les décisions passent par apps/backoffice/services.py (notification + journal d'audit).
"""

from django.db.models import Count, Q, Sum
from django.http import FileResponse, Http404
from rest_framework import mixins, parsers, status, viewsets
from rest_framework.decorators import action, api_view, permission_classes
from rest_framework.exceptions import ValidationError
from rest_framework.response import Response

from apps.accounts.models import User
from apps.catalog.models import Category, Product, Review
from apps.core.admin_views import IsPlatformAdmin
from apps.core.audit import audit
from apps.core.models import AuditLog, SiteSettings
from apps.delivery.models import Delivery, DeliveryZone, PickupPoint, Rider
from apps.orders.models import Order, VendorOrder
from apps.promotions.models import Campaign, Coupon
from apps.vendors.models import Vendor, VerificationRequest
from apps.wallet.models import CashDeposit, PayoutRequest
from apps.wallet.services import process_deposit, process_payout

from . import serializers as s
from . import services


class AdminViewSet(viewsets.GenericViewSet):
    permission_classes = [IsPlatformAdmin]
    parser_classes = [parsers.JSONParser, parsers.MultiPartParser, parsers.FormParser]


def text(request, key, required=False):
    value = str(request.data.get(key, "")).strip()
    if required and not value:
        raise ValidationError({key: "Ce champ est obligatoire."})
    return value


class AuditedCrudMixin:
    """Création / modification / suppression inscrites au journal."""

    audit_name = ""

    def perform_create(self, serializer):
        obj = serializer.save()
        audit(self.request, f"{self.audit_name}.created", obj)

    def perform_update(self, serializer):
        obj = serializer.save()
        audit(self.request, f"{self.audit_name}.updated", obj, {"fields": sorted(serializer.validated_data)})

    def perform_destroy(self, instance):
        audit(self.request, f"{self.audit_name}.deleted", instance)
        instance.delete()


# ---------------------------------------------------------------- Boutiques

class VendorViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, mixins.UpdateModelMixin, AdminViewSet):
    serializer_class = s.AdminVendorSerializer
    search_fields = ["shop_name", "user__email", "user__name", "user__phone"]
    filterset_fields = ["status", "is_verified", "is_premium"]
    ordering_fields = ["created_at", "revenue", "shop_name"]

    def get_queryset(self):
        live = ~Q(vendor_orders__status=VendorOrder.Status.CANCELLED)
        return (
            Vendor.objects.alive().select_related("user")
            .annotate(
                products_count=Count("products", filter=Q(products__deleted_at__isnull=True), distinct=True),
                orders_count=Count("vendor_orders", filter=live, distinct=True),
                revenue=Sum("vendor_orders__subtotal", filter=live),
            )
            .order_by("-created_at")
        )

    def perform_update(self, serializer):
        vendor = serializer.save()
        audit(self.request, "vendor.updated", vendor, {k: str(v) for k, v in serializer.validated_data.items()})

    @action(detail=True, methods=["post"])
    def approve(self, request, pk=None):
        services.approve_vendor(request.user, self.get_object())
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)

    @action(detail=True, methods=["post"])
    def suspend(self, request, pk=None):
        services.suspend_vendor(request.user, self.get_object(), text(request, "reason"))
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)

    @action(detail=True, methods=["post"], url_path="revoke-badge")
    def revoke_badge(self, request, pk=None):
        services.revoke_badge(request.user, self.get_object(), text(request, "reason"))
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)


class VerificationViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, AdminViewSet):
    serializer_class = s.AdminVerificationSerializer
    filterset_fields = ["status"]
    search_fields = ["vendor__shop_name", "document_number"]

    def get_queryset(self):
        return VerificationRequest.objects.select_related("vendor__user").order_by("status", "-created_at")

    @action(detail=True, methods=["post"])
    def approve(self, request, pk=None):
        services.decide_verification(request.user, self.get_object(), True)
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)

    @action(detail=True, methods=["post"])
    def reject(self, request, pk=None):
        services.decide_verification(request.user, self.get_object(), False, text(request, "note", required=True))
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)

    @action(detail=True, url_path=r"document/(?P<field>document_front|document_back|shop_photo)")
    def document(self, request, pk=None, field=None):
        """Pièce d'identité (stockage privé) : lue par l'équipe, chaque consultation journalisée."""
        vr = self.get_object()
        file = getattr(vr, field)
        if not file:
            raise Http404
        audit(request, "verification.document_viewed", vr, {"field": field})
        response = FileResponse(file.open("rb"))
        response["Cache-Control"] = "private, no-store"
        response["X-Content-Type-Options"] = "nosniff"
        return response


# ---------------------------------------------------------------- Livreurs et utilisateurs

class RiderViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, AdminViewSet):
    serializer_class = s.AdminRiderSerializer
    filterset_fields = ["is_approved", "is_available", "city"]
    search_fields = ["user__name", "user__email", "user__phone", "vehicle_plate"]

    def get_queryset(self):
        return Rider.objects.select_related("user").annotate(
            deliveries_done=Count("deliveries", filter=Q(deliveries__status=Delivery.Status.DELIVERED))
        ).order_by("is_approved", "-created_at")

    @action(detail=True, methods=["post"])
    def approve(self, request, pk=None):
        services.set_rider_approval(request.user, self.get_object(), True)
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)

    @action(detail=True, methods=["post"])
    def suspend(self, request, pk=None):
        services.set_rider_approval(request.user, self.get_object(), False)
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)


class UserViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, AdminViewSet):
    serializer_class = s.AdminUserSerializer
    filterset_fields = ["role", "is_active"]
    search_fields = ["name", "email", "phone"]
    ordering_fields = ["date_joined", "last_login", "name"]

    def get_queryset(self):
        return User.objects.select_related("vendor").order_by("-date_joined")

    def _toggle(self, request, active):
        try:
            services.set_user_active(request.user, self.get_object(), active)
        except ValueError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(self.get_object()).data)

    @action(detail=True, methods=["post"])
    def deactivate(self, request, pk=None):
        return self._toggle(request, False)

    @action(detail=True, methods=["post"])
    def activate(self, request, pk=None):
        return self._toggle(request, True)

    @action(detail=True, methods=["post"], url_path="reset-2fa")
    def reset_2fa(self, request, pk=None):
        user = self.get_object()
        if user.pk == request.user.pk:
            raise ValidationError({"detail": "Demandez à un autre membre de l'équipe de réinitialiser la vôtre."})
        services.reset_two_factor(request.user, user)
        return Response(self.get_serializer(user).data)


# ---------------------------------------------------------------- Commandes et argent

class OrderViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, AdminViewSet):
    filterset_fields = ["status", "payment_status", "payment_method", "delivery_method"]
    search_fields = ["id", "user__name", "user__email", "user__phone", "delivery_phone"]
    ordering_fields = ["created_at", "total_amount"]

    def get_queryset(self):
        return (
            Order.objects.alive().select_related("user")
            .prefetch_related("vendor_orders__vendor", "vendor_orders__items", "vendor_orders__delivery__rider__user")
            .order_by("-created_at")
        )

    def get_serializer_class(self):
        return s.AdminOrderDetailSerializer if self.action == "retrieve" else s.AdminOrderSerializer

    @action(detail=True, methods=["post"])
    def refunded(self, request, pk=None):
        try:
            services.mark_refunded(request.user, self.get_object(), text(request, "reference", required=True))
        except ValueError as e:
            raise ValidationError({"detail": str(e)})
        return Response(s.AdminOrderDetailSerializer(self.get_queryset().get(pk=pk)).data)

    @action(detail=True, methods=["post"], url_path=r"unlock-code/(?P<vendor_order_id>\d+)")
    def unlock_code(self, request, pk=None, vendor_order_id=None):
        vendor_order = VendorOrder.objects.filter(order_id=pk, pk=vendor_order_id).first()
        if vendor_order is None:
            raise Http404
        services.unlock_delivery_code(request.user, vendor_order)
        return Response(s.AdminOrderDetailSerializer(self.get_queryset().get(pk=pk)).data)


class PayoutViewSet(mixins.ListModelMixin, AdminViewSet):
    serializer_class = s.AdminPayoutSerializer
    filterset_fields = ["status", "method"]
    search_fields = ["user__name", "user__email", "phone", "transaction_reference"]

    def get_queryset(self):
        return PayoutRequest.objects.select_related("user").order_by("status", "-created_at")

    @action(detail=True, methods=["post"])
    def pay(self, request, pk=None):
        payout = process_payout(self.get_object(), request.user, paid=True, reference=text(request, "reference", required=True))
        return Response(self.get_serializer(payout).data)

    @action(detail=True, methods=["post"])
    def reject(self, request, pk=None):
        payout = process_payout(self.get_object(), request.user, paid=False, note=text(request, "note", required=True))
        return Response(self.get_serializer(payout).data)


class DepositViewSet(mixins.ListModelMixin, AdminViewSet):
    serializer_class = s.AdminDepositSerializer
    filterset_fields = ["status", "method"]
    search_fields = ["user__name", "user__email", "reference"]

    def get_queryset(self):
        return CashDeposit.objects.select_related("user").order_by("status", "-created_at")

    @action(detail=True, methods=["post"])
    def confirm(self, request, pk=None):
        return Response(self.get_serializer(process_deposit(self.get_object(), request.user, confirmed=True)).data)

    @action(detail=True, methods=["post"])
    def reject(self, request, pk=None):
        deposit = process_deposit(self.get_object(), request.user, confirmed=False, note=text(request, "note", required=True))
        return Response(self.get_serializer(deposit).data)


# ---------------------------------------------------------------- Catalogue

class CategoryViewSet(AuditedCrudMixin, viewsets.ModelViewSet, AdminViewSet):
    serializer_class = s.AdminCategorySerializer
    audit_name = "category"
    pagination_class = None
    search_fields = ["name"]

    def get_queryset(self):
        return Category.objects.annotate(
            products_count=Count("products", filter=Q(products__deleted_at__isnull=True))
        ).order_by("name")

    def perform_destroy(self, instance):
        if instance.products.alive().exists():
            raise ValidationError({"detail": "Cette catégorie contient des produits : désactivez-la plutôt."})
        super().perform_destroy(instance)


class ProductViewSet(mixins.ListModelMixin, mixins.UpdateModelMixin, AdminViewSet):
    """Modération : l'équipe peut masquer un produit (contenu interdit, arnaque…), pas le modifier."""

    serializer_class = s.AdminProductSerializer
    filterset_fields = ["status", "vendor__slug", "category__slug"]
    search_fields = ["name", "vendor__shop_name"]

    def get_queryset(self):
        return Product.objects.alive().select_related("vendor", "category").prefetch_related("images").order_by("-created_at")

    def perform_update(self, serializer):
        product = serializer.save()
        audit(self.request, f"product.{product.status}", product)


class ReviewViewSet(mixins.ListModelMixin, mixins.UpdateModelMixin, AdminViewSet):
    serializer_class = s.AdminReviewSerializer
    filterset_fields = ["is_visible", "rating"]
    search_fields = ["comment", "product__name", "user__name"]

    def get_queryset(self):
        return Review.objects.select_related("user", "product").order_by("-created_at")

    def perform_update(self, serializer):
        review = serializer.save()
        audit(self.request, "review.shown" if review.is_visible else "review.hidden", review.product, {"review": review.pk})


# ---------------------------------------------------------------- Promotions, livraison, réglages

class CouponViewSet(AuditedCrudMixin, viewsets.ModelViewSet, AdminViewSet):
    serializer_class = s.AdminCouponSerializer
    audit_name = "coupon"
    filterset_fields = ["is_active", "type"]
    search_fields = ["code", "description", "vendor__shop_name"]

    def get_queryset(self):
        return Coupon.objects.select_related("vendor").order_by("-created_at")


class CampaignViewSet(AuditedCrudMixin, viewsets.ModelViewSet, AdminViewSet):
    serializer_class = s.AdminCampaignSerializer
    audit_name = "campaign"
    pagination_class = None

    def get_queryset(self):
        return Campaign.objects.select_related("coupon").prefetch_related("products").order_by("-starts_at")


class ZoneViewSet(AuditedCrudMixin, viewsets.ModelViewSet, AdminViewSet):
    serializer_class = s.AdminZoneSerializer
    audit_name = "delivery_zone"
    pagination_class = None
    queryset = DeliveryZone.objects.order_by("city", "commune")


class PickupPointViewSet(AuditedCrudMixin, viewsets.ModelViewSet, AdminViewSet):
    serializer_class = s.AdminPickupPointSerializer
    audit_name = "pickup_point"
    pagination_class = None
    queryset = PickupPoint.objects.order_by("city", "name")


class AuditLogViewSet(mixins.ListModelMixin, AdminViewSet):
    serializer_class = s.AuditLogSerializer
    filterset_fields = ["action"]
    search_fields = ["action", "target", "actor__name", "ip"]

    def get_queryset(self):
        return AuditLog.objects.select_related("actor").order_by("-created_at")


@api_view(["GET", "PATCH"])
@permission_classes([IsPlatformAdmin])
def site_settings(request):
    site = SiteSettings.load()
    if request.method == "GET":
        return Response(s.AdminSettingsSerializer(site, context={"request": request}).data)
    serializer = s.AdminSettingsSerializer(site, data=request.data, partial=True, context={"request": request})
    serializer.is_valid(raise_exception=True)
    serializer.save()
    audit(request, "site_settings.changed", site, {"fields": sorted(serializer.validated_data)})
    return Response(serializer.data, status=status.HTTP_200_OK)
