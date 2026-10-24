from django.contrib.auth.password_validation import validate_password
from django.db import transaction
from rest_framework import serializers
from rest_framework_simplejwt.tokens import RefreshToken

from apps.vendors.models import Vendor

from .models import Address, User


class UserSerializer(serializers.ModelSerializer):
    shop = serializers.SerializerMethodField()

    class Meta:
        model = User
        fields = ["id", "email", "name", "phone", "role", "avatar", "referral_code", "shop", "date_joined"]
        read_only_fields = ["id", "email", "role", "referral_code", "date_joined"]

    def get_shop(self, user):
        vendor = getattr(user, "vendor", None)
        if vendor is None:
            return None
        return {"id": vendor.id, "slug": vendor.slug, "shop_name": vendor.shop_name, "status": vendor.status}


class RegisterSerializer(serializers.ModelSerializer):
    password = serializers.CharField(write_only=True, validators=[validate_password])
    referral = serializers.CharField(write_only=True, required=False, allow_blank=True, max_length=12)
    tokens = serializers.SerializerMethodField()

    class Meta:
        model = User
        fields = ["id", "email", "name", "phone", "password", "referral", "tokens"]

    def validate_referral(self, code):
        if not code:
            return None
        parrain = User.objects.filter(referral_code=code.upper().strip()).first()
        if parrain is None:
            raise serializers.ValidationError("Code parrain inconnu.")
        return parrain

    def create(self, validated_data):
        return User.objects.create_user(role=User.Role.CLIENT, **self._with_referral(validated_data))

    def _with_referral(self, validated_data):
        parrain = validated_data.pop("referral", None)
        if parrain:
            validated_data["referred_by"] = parrain
        return validated_data

    def save(self, **kwargs):
        user = super().save(**kwargs)
        if user.referred_by_id:
            from apps.promotions.services import create_referral_coupon

            create_referral_coupon(user, f"Bienvenue de la part de {user.referred_by.name}")
        return user

    def get_tokens(self, user):
        refresh = RefreshToken.for_user(user)
        return {"refresh": str(refresh), "access": str(refresh.access_token)}


class VendorRegisterSerializer(RegisterSerializer):
    """Inscription vendeur : crée le compte et la boutique (en attente de validation)."""

    shop_name = serializers.CharField(max_length=255, write_only=True)
    shop_description = serializers.CharField(required=False, allow_blank=True, write_only=True)
    whatsapp = serializers.CharField(max_length=20, required=False, allow_blank=True, write_only=True)

    class Meta(RegisterSerializer.Meta):
        fields = RegisterSerializer.Meta.fields + ["shop_name", "shop_description", "whatsapp"]

    @transaction.atomic
    def create(self, validated_data):
        shop = {
            "shop_name": validated_data.pop("shop_name"),
            "description": validated_data.pop("shop_description", ""),
            "whatsapp": validated_data.pop("whatsapp", ""),
        }
        user = User.objects.create_user(role=User.Role.VENDEUR, **self._with_referral(validated_data))
        Vendor.objects.create(user=user, **shop)
        return user


class AddressSerializer(serializers.ModelSerializer):
    class Meta:
        model = Address
        fields = [
            "id", "label", "city", "commune", "quartier", "landmark",
            "latitude", "longitude", "contact_phone", "is_default",
        ]

    def save(self, **kwargs):
        address = super().save(**kwargs)
        if address.is_default:
            address.user.addresses.exclude(pk=address.pk).update(is_default=False)
        return address
