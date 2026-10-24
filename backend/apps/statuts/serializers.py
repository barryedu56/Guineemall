from rest_framework import serializers

from apps.catalog.models import Product

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
        content_type = getattr(media, "content_type", "") or ""
        if content_type.startswith("image/"):
            kind, limit = Statut.MediaType.IMAGE, MAX_IMAGE_SIZE
        elif content_type.startswith("video/"):
            kind, limit = Statut.MediaType.VIDEO, MAX_VIDEO_SIZE
        else:
            raise serializers.ValidationError("Seules les images et les vidéos sont acceptées.")
        if media.size > limit:
            raise serializers.ValidationError(f"Fichier trop lourd (maximum {limit // (1024 * 1024)} Mo).")
        self._media_type = kind
        return media

    def create(self, validated_data):
        validated_data["media_type"] = self._media_type
        return super().create(validated_data)
