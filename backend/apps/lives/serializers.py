import re

from rest_framework import serializers

from apps.catalog.models import Product
from apps.catalog.serializers import ProductListSerializer

from .models import LiveSession


def embed_url(url):
    """Transforme un lien YouTube / Facebook en lien intégrable dans la page (TikTok : lien direct)."""
    yt = re.search(r"(?:youtube\.com/(?:watch\?v=|live/)|youtu\.be/)([\w-]{11})", url)
    if yt:
        return f"https://www.youtube.com/embed/{yt.group(1)}?autoplay=1"
    if "facebook.com" in url or "fb.watch" in url:
        return f"https://www.facebook.com/plugins/video.php?href={url}&autoplay=true"
    return None


class LiveSerializer(serializers.ModelSerializer):
    shop = serializers.SerializerMethodField()
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    embed_url = serializers.SerializerMethodField()

    class Meta:
        model = LiveSession
        fields = ["id", "title", "description", "stream_url", "embed_url", "starts_at", "status", "status_label", "shop"]

    def get_shop(self, live):
        v = live.vendor
        return {"slug": v.slug, "shop_name": v.shop_name, "logo": v.logo.url if v.logo else None}

    def get_embed_url(self, live):
        return embed_url(live.stream_url)


class LiveDetailSerializer(LiveSerializer):
    products = serializers.SerializerMethodField()
    pinned_product = serializers.SlugRelatedField(slug_field="slug", read_only=True)
    comments = serializers.SerializerMethodField()

    class Meta(LiveSerializer.Meta):
        fields = LiveSerializer.Meta.fields + ["products", "pinned_product", "comments"]

    def get_products(self, live):
        from apps.catalog.views import public_products

        return ProductListSerializer(public_products().filter(lives=live), many=True, context=self.context).data

    def get_comments(self, live):
        comments = live.comments.select_related("user").order_by("-created_at")[:50]
        return [
            {"id": c.id, "author": c.user.name.split()[0], "text": c.text, "created_at": c.created_at}
            for c in reversed(comments)
        ]


class VendorLiveSerializer(serializers.ModelSerializer):
    product_slugs = serializers.SlugRelatedField(
        source="products", slug_field="slug", many=True, queryset=Product.objects.none(), required=False
    )

    class Meta:
        model = LiveSession
        fields = ["id", "title", "description", "stream_url", "starts_at", "status", "product_slugs"]
        read_only_fields = ["status"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        vendor = self.context.get("vendor")
        if vendor is not None:
            self.fields["product_slugs"].child_relation.queryset = vendor.products.alive()
