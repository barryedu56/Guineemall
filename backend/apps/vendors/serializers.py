from rest_framework import serializers

from .models import Vendor, VerificationRequest


class VendorSerializer(serializers.ModelSerializer):
    rating = serializers.FloatField(read_only=True, default=None)
    reviews_count = serializers.IntegerField(read_only=True, default=0)
    products_count = serializers.IntegerField(read_only=True, default=0)

    class Meta:
        model = Vendor
        fields = [
            "id", "slug", "shop_name", "description", "logo", "whatsapp", "is_verified", "is_premium",
            "rating", "reviews_count", "products_count", "created_at",
        ]


PICKUP_FIELDS = [
    "pickup_city", "pickup_commune", "pickup_quartier", "pickup_landmark", "pickup_phone", "pickup_hours",
    "pickup_latitude", "pickup_longitude",
]


class MyShopSerializer(serializers.ModelSerializer):
    """Le vendeur modifie sa propre boutique (il ne peut pas changer son statut ni son badge)."""

    has_pickup_address = serializers.BooleanField(read_only=True)

    class Meta:
        model = Vendor
        fields = [
            "id", "slug", "shop_name", "description", "logo", "whatsapp", "status", "is_verified",
            *PICKUP_FIELDS, "has_pickup_address",
        ]
        read_only_fields = ["id", "slug", "status", "is_verified"]

    def validate(self, attrs):
        city = attrs.get("pickup_city")
        commune = attrs.get("pickup_commune")
        if city and commune:
            from apps.delivery.models import DeliveryZone

            if not DeliveryZone.objects.filter(is_active=True, city__iexact=city, commune__iexact=commune).exists() \
                    and not DeliveryZone.objects.filter(is_active=True, city__iexact=city, commune="").exists():
                raise serializers.ValidationError({"pickup_commune": "Cette commune n'est pas encore desservie par nos livreurs."})
        return attrs


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
