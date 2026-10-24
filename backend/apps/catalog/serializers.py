from rest_framework import serializers

from apps.vendors.serializers import VendorSerializer

from .models import Category, Product, ProductImage


class CategorySerializer(serializers.ModelSerializer):
    class Meta:
        model = Category
        fields = ["id", "name", "slug", "description", "image", "parent"]


class ProductImageSerializer(serializers.ModelSerializer):
    class Meta:
        model = ProductImage
        fields = ["id", "image", "position"]


class ProductListSerializer(serializers.ModelSerializer):
    image = serializers.SerializerMethodField()
    vendor = serializers.SlugRelatedField(slug_field="slug", read_only=True)
    category = serializers.SlugRelatedField(slug_field="slug", read_only=True)

    class Meta:
        model = Product
        fields = ["id", "slug", "name", "price", "compare_at_price", "stock", "image", "vendor", "category"]

    def get_image(self, product):
        # Utilise les images préchargées (prefetch_related) pour éviter une requête par produit
        images = list(product.images.all())
        if not images:
            return None
        request = self.context.get("request")
        url = images[0].image.url
        return request.build_absolute_uri(url) if request else url


class ProductDetailSerializer(ProductListSerializer):
    images = ProductImageSerializer(many=True, read_only=True)
    vendor = VendorSerializer(read_only=True)
    category = CategorySerializer(read_only=True)

    class Meta(ProductListSerializer.Meta):
        fields = ProductListSerializer.Meta.fields + ["description", "attributes", "images", "created_at"]


class VendorProductSerializer(serializers.ModelSerializer):
    """Produit vu et modifié par son vendeur."""

    images = ProductImageSerializer(many=True, read_only=True)
    category = serializers.SlugRelatedField(
        slug_field="slug", queryset=Category.objects.filter(status=Category.Status.ACTIVE)
    )

    class Meta:
        model = Product
        fields = [
            "id", "slug", "name", "description", "price", "compare_at_price", "stock",
            "attributes", "status", "category", "images", "created_at", "updated_at",
        ]
        read_only_fields = ["id", "slug", "created_at", "updated_at"]

    def validate(self, attrs):
        price = attrs.get("price", getattr(self.instance, "price", None))
        compare = attrs.get("compare_at_price", getattr(self.instance, "compare_at_price", None))
        if compare is not None and price is not None and compare <= price:
            raise serializers.ValidationError(
                {"compare_at_price": "L'ancien prix doit être supérieur au prix actuel."}
            )
        return attrs
