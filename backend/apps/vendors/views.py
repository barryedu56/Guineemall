from rest_framework import generics, permissions, viewsets

from .models import Vendor
from .serializers import MyShopSerializer, VendorSerializer


def public_vendors():
    return Vendor.objects.alive().filter(status=Vendor.Status.APPROVED)


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
