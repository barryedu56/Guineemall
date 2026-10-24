from rest_framework import serializers

from apps.catalog.serializers import ProductListSerializer

from .models import Campaign, Coupon


class CampaignSerializer(serializers.ModelSerializer):
    coupon_code = serializers.CharField(source="coupon.code", read_only=True, default=None)

    class Meta:
        model = Campaign
        fields = ["id", "name", "slug", "description", "banner", "color", "starts_at", "ends_at", "coupon_code"]


class CampaignDetailSerializer(CampaignSerializer):
    products = serializers.SerializerMethodField()

    class Meta(CampaignSerializer.Meta):
        fields = CampaignSerializer.Meta.fields + ["products"]

    def get_products(self, campaign):
        from apps.catalog.views import public_products

        products = public_products().filter(campaigns=campaign)
        return ProductListSerializer(products, many=True, context=self.context).data


class VendorCouponSerializer(serializers.ModelSerializer):
    class Meta:
        model = Coupon
        fields = [
            "id", "code", "type", "value", "max_discount", "min_amount", "starts_at", "ends_at",
            "usage_limit", "per_user_limit", "used_count", "is_active", "description",
        ]
        read_only_fields = ["used_count"]

    def validate_code(self, code):
        code = code.upper().strip()
        if Coupon.objects.filter(code=code).exclude(pk=getattr(self.instance, "pk", None)).exists():
            raise serializers.ValidationError("Ce code existe déjà.")
        return code

    def validate(self, attrs):
        if attrs.get("type", getattr(self.instance, "type", None)) == Coupon.Type.PERCENT and attrs.get("value", 0) > 90:
            raise serializers.ValidationError({"value": "90 % maximum."})
        return attrs
