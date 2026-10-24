from django.db.models import Avg, Count, Q
from rest_framework import generics, mixins, parsers, permissions, viewsets
from rest_framework.exceptions import ValidationError

from .models import Vendor, VerificationRequest
from .serializers import MyShopSerializer, VendorSerializer, VerificationRequestSerializer


def public_vendors():
    visible = Q(products__reviews__is_visible=True)
    return Vendor.objects.alive().filter(status=Vendor.Status.APPROVED).annotate(
        rating=Avg("products__reviews__rating", filter=visible),
        reviews_count=Count("products__reviews", filter=visible),
    )


class VendorViewSet(viewsets.ReadOnlyModelViewSet):
    serializer_class = VendorSerializer
    lookup_field = "slug"
    search_fields = ["shop_name"]

    def get_queryset(self):
        return public_vendors()


class MyShopView(generics.RetrieveUpdateAPIView):
    """Accessible même si la boutique est en attente, pour que le vendeur la complète."""

    serializer_class = MyShopSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_object(self):
        return generics.get_object_or_404(Vendor.objects.alive(), user=self.request.user)


class VerificationRequestViewSet(mixins.ListModelMixin, mixins.CreateModelMixin, viewsets.GenericViewSet):
    """Le vendeur envoie ses justificatifs pour obtenir le badge « Vendeur vérifié »."""

    serializer_class = VerificationRequestSerializer
    permission_classes = [permissions.IsAuthenticated]
    parser_classes = [parsers.MultiPartParser, parsers.FormParser]
    pagination_class = None

    def get_vendor(self):
        return generics.get_object_or_404(Vendor.objects.alive(), user=self.request.user)

    def get_queryset(self):
        return self.get_vendor().verification_requests.all()

    def perform_create(self, serializer):
        vendor = self.get_vendor()
        if vendor.is_verified:
            raise ValidationError({"detail": "Votre boutique est déjà vérifiée."})
        if vendor.verification_requests.filter(status=VerificationRequest.Status.PENDING).exists():
            raise ValidationError({"detail": "Une demande est déjà en cours d'examen."})
        serializer.save(vendor=vendor)
