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
        fields = ["id", "email", "name", "phone", "role", "avatar", "shop", "date_joined"]
        read_only_fields = ["id", "email", "role", "date_joined"]

    def get_shop(self, user):
        vendor = getattr(user, "vendor", None)
        if vendor is None:
            return None
        return {"id": vendor.id, "slug": vendor.slug, "shop_name": vendor.shop_name, "status": vendor.status}


class RegisterSerializer(serializers.ModelSerializer):
    password = serializers.CharField(write_only=True, validators=[validate_password])
    tokens = serializers.SerializerMethodField()

    class Meta:
        model = User
        fields = ["id", "email", "name", "phone", "password", "tokens"]

    def create(self, validated_data):
        return User.objects.create_user(role=User.Role.CLIENT, **validated_data)

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
        user = User.objects.create_user(role=User.Role.VENDEUR, **validated_data)
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
