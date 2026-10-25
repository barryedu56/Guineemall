from django.core.exceptions import ValidationError as DjangoValidationError
from rest_framework import serializers

from apps.catalog.models import Product
from apps.core.uploads import check_upload

from .models import Statut

MAX_IMAGE_SIZE = 5 * 1024 * 1024
MAX_VIDEO_SIZE = 30 * 1024 * 1024


class StatutSerializer(serializers.ModelSerializer):
    """Statut tel que le voit un client."""

    product = serializers.SerializerMethodField()
    seen = serializers.SerializerMethodField()

    class Meta:
        model = Statut
        fields = ["id", "media", "media_type", "caption", "product", "seen", "created_at", "expires_at"]

    def get_product(self, statut):
        if statut.product is None:
            return None
        p = statut.product
        return {"slug": p.slug, "name": p.name, "price": p.price}

    def get_seen(self, statut):
        return statut.id in self.context.get("seen_ids", set())


class VendorStatutSerializer(serializers.ModelSerializer):
    """Statut vu et publié par son vendeur."""

    product_slug = serializers.SlugRelatedField(
        source="product", slug_field="slug", queryset=Product.objects.none(),
        required=False, allow_null=True,
    )
    views_count = serializers.IntegerField(read_only=True)
    is_active = serializers.BooleanField(read_only=True)

    class Meta:
        model = Statut
        fields = [
            "id", "media", "media_type", "caption", "product_slug",
            "views_count", "is_active", "created_at", "expires_at",
        ]
        read_only_fields = ["id", "media_type", "created_at", "expires_at"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        vendor = self.context.get("vendor")
        if vendor is not None:
            # Un vendeur ne peut lier que ses propres produits
            self.fields["product_slug"].queryset = vendor.products.alive()

    def validate_media(self, media):
        try:
            family = check_upload(media, {"image", "video"}, max_mb=MAX_VIDEO_SIZE // (1024 * 1024))
        except DjangoValidationError as e:
            raise serializers.ValidationError(e.messages[0])
        if family == "image" and media.size > MAX_IMAGE_SIZE:
            raise serializers.ValidationError(f"Image trop lourde ({MAX_IMAGE_SIZE // (1024 * 1024)} Mo maximum).")
        self._media_type = Statut.MediaType.IMAGE if family == "image" else Statut.MediaType.VIDEO
        return media

    def create(self, validated_data):
        validated_data["media_type"] = self._media_type
        return super().create(validated_data)
