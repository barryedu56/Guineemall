from rest_framework import serializers

from apps.vendors.serializers import VendorSerializer

from .models import Category, Product, ProductImage, Review, ReviewPhoto


class CategorySerializer(serializers.ModelSerializer):
    products_count = serializers.IntegerField(read_only=True, default=0)

    class Meta:
        model = Category
        fields = ["id", "name", "slug", "description", "image", "parent", "products_count"]


class ProductImageSerializer(serializers.ModelSerializer):
    class Meta:
        model = ProductImage
        fields = ["id", "image", "position"]


class ProductListSerializer(serializers.ModelSerializer):
    image = serializers.SerializerMethodField()
    vendor = serializers.SlugRelatedField(slug_field="slug", read_only=True)
    category = serializers.SlugRelatedField(slug_field="slug", read_only=True)

    rating = serializers.FloatField(read_only=True, default=None)
    reviews_count = serializers.IntegerField(read_only=True, default=0)

    class Meta:
        model = Product
        fields = [
            "id", "slug", "name", "price", "compare_at_price", "stock", "image", "vendor", "category",
            "rating", "reviews_count",
        ]

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


class ReviewPhotoSerializer(serializers.ModelSerializer):
    class Meta:
        model = ReviewPhoto
        fields = ["id", "image"]


class ReviewSerializer(serializers.ModelSerializer):
    author = serializers.SerializerMethodField()
    photos = ReviewPhotoSerializer(many=True, read_only=True)
    uploaded_photos = serializers.ListField(
        child=serializers.ImageField(), write_only=True, required=False, max_length=4
    )

    class Meta:
        model = Review
        fields = ["id", "author", "rating", "comment", "photos", "uploaded_photos", "vendor_reply", "created_at"]
        read_only_fields = ["vendor_reply"]

    def get_author(self, review):
        # Prénom + initiale du nom, pour la vie privée : « Aïssatou S. »
        parts = review.user.name.split()
        return f"{parts[0]} {parts[-1][0]}." if len(parts) > 1 else parts[0] if parts else "Client"

    def create(self, validated_data):
        photos = validated_data.pop("uploaded_photos", [])
        review = super().create(validated_data)
        ReviewPhoto.objects.bulk_create(ReviewPhoto(review=review, image=p) for p in photos)
        return review
