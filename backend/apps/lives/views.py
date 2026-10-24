from asgiref.sync import async_to_sync
from channels.layers import get_channel_layer
from rest_framework import viewsets
from rest_framework.decorators import action
from rest_framework.exceptions import ValidationError
from rest_framework.response import Response

from apps.accounts.models import User
from apps.core.permissions import IsVendeur, get_vendor
from apps.notifications.services import notify
from apps.vendors.models import Vendor

from .models import LiveSession
from .serializers import LiveDetailSerializer, LiveSerializer, VendorLiveSerializer


def broadcast(live, event):
    layer = get_channel_layer()
    if layer:
        try:
            async_to_sync(layer.group_send)(f"live_{live.id}", {"type": "push", "event": event})
        except Exception:
            pass


class LiveViewSet(viewsets.ReadOnlyModelViewSet):
    """Directs en cours puis à venir."""

    pagination_class = None

    def get_queryset(self):
        return (
            LiveSession.objects.exclude(status=LiveSession.Status.ENDED)
            .filter(vendor__status=Vendor.Status.APPROVED)
            .select_related("vendor", "pinned_product")
            .order_by("-status", "starts_at")  # « live » avant « scheduled »
        )

    def get_serializer_class(self):
        return LiveDetailSerializer if self.action == "retrieve" else LiveSerializer


class VendorLiveViewSet(viewsets.ModelViewSet):
    serializer_class = VendorLiveSerializer
    permission_classes = [IsVendeur]
    pagination_class = None

    def get_queryset(self):
        return get_vendor(self.request).lives.all()

    def get_serializer_context(self):
        return {**super().get_serializer_context(), "vendor": get_vendor(self.request)}

    def perform_create(self, serializer):
        serializer.save(vendor=get_vendor(self.request))

    @action(detail=True, methods=["post"])
    def start(self, request, pk=None):
        live = self.get_object()
        live.status = LiveSession.Status.LIVE
        live.save(update_fields=["status", "updated_at"])
        # Les clients qui ont un produit de la boutique en favori sont prévenus
        fans = User.objects.filter(favorites__product__vendor=live.vendor).distinct()
        for user in fans:
            notify(user, "promo", f"🔴 {live.vendor.shop_name} est en direct", live.title, f"/lives/{live.id}", whatsapp=False)
        broadcast(live, {"type": "live.status", "status": live.status})
        return Response(self.get_serializer(live).data)

    @action(detail=True, methods=["post"])
    def end(self, request, pk=None):
        live = self.get_object()
        live.status = LiveSession.Status.ENDED
        live.save(update_fields=["status", "updated_at"])
        broadcast(live, {"type": "live.status", "status": live.status})
        return Response(self.get_serializer(live).data)

    @action(detail=True, methods=["post"])
    def pin(self, request, pk=None):
        """Épingle le produit présenté en ce moment (affiché à tous les spectateurs)."""
        live = self.get_object()
        slug = request.data.get("product")
        product = live.products.filter(slug=slug).first() if slug else None
        if slug and product is None:
            raise ValidationError({"product": "Ce produit ne fait pas partie du direct."})
        live.pinned_product = product
        live.save(update_fields=["pinned_product", "updated_at"])
        broadcast(live, {"type": "live.pinned", "product": slug or None})
        return Response(self.get_serializer(live).data)
