from rest_framework import serializers

from .models import Vendor, VerificationRequest


class VendorSerializer(serializers.ModelSerializer):
    rating = serializers.FloatField(read_only=True, default=None)
    reviews_count = serializers.IntegerField(read_only=True, default=0)

    class Meta:
        model = Vendor
        fields = [
            "id", "slug", "shop_name", "description", "logo", "whatsapp", "is_verified", "is_premium",
            "rating", "reviews_count", "created_at",
        ]


class MyShopSerializer(serializers.ModelSerializer):
    """Le vendeur modifie sa propre boutique (il ne peut pas changer son statut ni son badge)."""

    class Meta:
        model = Vendor
        fields = ["id", "slug", "shop_name", "description", "logo", "whatsapp", "status", "is_verified"]
        read_only_fields = ["id", "slug", "status", "is_verified"]


class VerificationRequestSerializer(serializers.ModelSerializer):
    status_label = serializers.CharField(source="get_status_display", read_only=True)

    class Meta:
        model = VerificationRequest
        fields = [
            "id", "document_type", "document_number", "document_front", "document_back", "shop_photo",
            "status", "status_label", "admin_note", "created_at",
        ]
        read_only_fields = ["status", "admin_note"]
        extra_kwargs = {
            # Pièces d'identité : jamais renvoyées dans l'API, seulement visibles par les admins
            "document_front": {"write_only": True},
            "document_back": {"write_only": True},
            "shop_photo": {"write_only": True},
        }
