from rest_framework import serializers

from .models import Vendor


class VendorSerializer(serializers.ModelSerializer):
    class Meta:
        model = Vendor
        fields = ["id", "slug", "shop_name", "description", "logo", "whatsapp", "is_verified", "created_at"]


class MyShopSerializer(serializers.ModelSerializer):
    """Le vendeur modifie sa propre boutique (il ne peut pas changer son statut ni son badge)."""

    class Meta:
        model = Vendor
        fields = ["id", "slug", "shop_name", "description", "logo", "whatsapp", "status", "is_verified"]
        read_only_fields = ["id", "slug", "status", "is_verified"]
