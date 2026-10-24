from django.utils import timezone
from rest_framework import viewsets

from apps.core.permissions import IsVendeur, get_vendor

from .models import Campaign
from .serializers import CampaignDetailSerializer, CampaignSerializer, VendorCouponSerializer


class CampaignViewSet(viewsets.ReadOnlyModelViewSet):
    """Opérations commerciales en cours (Tabaski, Ramadan…)."""

    lookup_field = "slug"
    pagination_class = None

    def get_queryset(self):
        now = timezone.now()
        return Campaign.objects.filter(is_active=True, starts_at__lte=now, ends_at__gte=now).select_related("coupon")

    def get_serializer_class(self):
        return CampaignDetailSerializer if self.action == "retrieve" else CampaignSerializer


class VendorCouponViewSet(viewsets.ModelViewSet):
    """Codes promo créés par la boutique (valables uniquement sur ses produits)."""

    serializer_class = VendorCouponSerializer
    permission_classes = [IsVendeur]
    pagination_class = None

    def get_queryset(self):
        return get_vendor(self.request).coupons.order_by("-created_at")

    def perform_create(self, serializer):
        serializer.save(vendor=get_vendor(self.request))
