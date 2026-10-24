from rest_framework import serializers

from apps.accounts.models import Address
from apps.core.phone import PhoneSerializerField
from apps.delivery.models import PickupPoint
from apps.catalog.models import Product
from apps.catalog.serializers import ProductListSerializer

from .models import CartItem, Order, OrderItem, OrderStatusHistory, VendorOrder
from .services import VENDOR_ORDER_TRANSITIONS


class CartItemSerializer(serializers.ModelSerializer):
    product = ProductListSerializer(read_only=True)
    product_slug = serializers.SlugRelatedField(
        source="product", slug_field="slug", write_only=True,
        # Seuls les produits visibles (actifs, boutique approuvée) peuvent aller au panier
        queryset=Product.objects.alive().filter(
            status=Product.Status.ACTIVE, vendor__status="approved", vendor__deleted_at__isnull=True
        ).select_related("vendor"),
    )
    quantity = serializers.IntegerField(min_value=1)
    subtotal = serializers.IntegerField(read_only=True)

    class Meta:
        model = CartItem
        fields = ["id", "product", "product_slug", "quantity", "subtotal"]

    def validate(self, attrs):
        product = attrs.get("product", getattr(self.instance, "product", None))
        if product and product.is_owned_by(self.context["request"].user):
            raise serializers.ValidationError({"product_slug": "Vous ne pouvez pas acheter un produit de votre propre boutique."})
        if product and attrs.get("quantity", 1) > product.stock:
            raise serializers.ValidationError({"quantity": f"Il ne reste que {product.stock} en stock."})
        return attrs


class QuoteSerializer(serializers.Serializer):
    address = serializers.PrimaryKeyRelatedField(queryset=Address.objects.none(), required=False, allow_null=True)
    pickup_point = serializers.PrimaryKeyRelatedField(
        queryset=PickupPoint.objects.filter(is_active=True), required=False, allow_null=True
    )
    coupon_code = serializers.CharField(required=False, allow_blank=True, max_length=30)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Le client ne peut utiliser que ses propres adresses
        self.fields["address"].queryset = self.context["request"].user.addresses.all()

    def validate(self, attrs):
        if not attrs.get("address") and not attrs.get("pickup_point"):
            raise serializers.ValidationError("Choisissez une adresse de livraison ou un point relais.")
        if attrs.get("pickup_point"):
            attrs["address"] = None
        return attrs


class CheckoutSerializer(QuoteSerializer):
    payment_method = serializers.ChoiceField(choices=Order.PaymentMethod.choices)
    affiliate_code = serializers.CharField(required=False, allow_blank=True, max_length=12)
    delivery_name = serializers.CharField(required=False, allow_blank=True, max_length=150)

    def validate_payment_method(self, method):
        from apps.core.models import SiteSettings

        if method not in SiteSettings.load().enabled_payment_methods():
            raise serializers.ValidationError("Ce mode de paiement n'est pas disponible pour le moment.")
        return method


class PaySerializer(serializers.Serializer):
    phone = PhoneSerializerField(required=False, allow_blank=True)


class OrderItemSerializer(serializers.ModelSerializer):
    product_slug = serializers.SlugRelatedField(source="product", slug_field="slug", read_only=True)
    subtotal = serializers.IntegerField(read_only=True)

    class Meta:
        model = OrderItem
        fields = ["id", "product_slug", "product_name", "unit_price", "quantity", "subtotal"]


def tracking(vendor_order):
    """Suivi du livreur GuinéeMall, s'il y en a un."""
    delivery = getattr(vendor_order, "delivery", None)
    if delivery is None or delivery.status == "cancelled":
        return None
    rider = delivery.rider
    return {
        "status": delivery.status,
        "status_label": delivery.get_status_display(),
        "rider_name": rider.user.name if rider else None,
        "rider_phone": rider.user.phone if rider else None,
        "latitude": rider.latitude if rider else None,
        "longitude": rider.longitude if rider else None,
    }


class VendorOrderSerializer(serializers.ModelSerializer):
    shop_name = serializers.CharField(source="vendor.shop_name", read_only=True)
    shop_slug = serializers.CharField(source="vendor.slug", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    items = OrderItemSerializer(many=True, read_only=True)
    tracking = serializers.SerializerMethodField()

    class Meta:
        model = VendorOrder
        fields = [
            "id", "shop_name", "shop_slug", "subtotal", "delivery_fee", "vendor_discount", "platform_discount",
            "total_amount", "status", "status_label", "received_at", "items", "tracking",
        ]

    def get_tracking(self, vendor_order):
        return tracking(vendor_order)


class StatusHistorySerializer(serializers.ModelSerializer):
    class Meta:
        model = OrderStatusHistory
        fields = ["status", "note", "created_at"]


DELIVERY_FIELDS = [
    "delivery_city", "delivery_commune", "delivery_quartier", "delivery_landmark",
    "delivery_latitude", "delivery_longitude", "delivery_phone",
]


class OrderSerializer(serializers.ModelSerializer):
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    payment_method_label = serializers.CharField(source="get_payment_method_display", read_only=True)
    payment_status_label = serializers.CharField(source="get_payment_status_display", read_only=True)
    vendor_orders = VendorOrderSerializer(many=True, read_only=True)
    status_history = StatusHistorySerializer(many=True, read_only=True)
    coupon_code = serializers.CharField(source="coupon.code", read_only=True, default=None)
    pickup_point_name = serializers.CharField(source="pickup_point.name", read_only=True, default=None)
    awaiting_payment = serializers.SerializerMethodField()
    can_confirm_reception = serializers.SerializerMethodField()

    class Meta:
        model = Order
        fields = [
            "id", "subtotal", "delivery_fee", "discount_amount", "total_amount", "coupon_code",
            "status", "status_label", "payment_method", "payment_method_label", "payment_status", "payment_status_label",
            "awaiting_payment", "can_confirm_reception", "delivery_method", "pickup_point_name",
            "delivery_name", *DELIVERY_FIELDS, "vendor_orders", "status_history", "created_at",
        ]

    def get_awaiting_payment(self, order):
        return (
            order.payment_method != Order.PaymentMethod.CASH_ON_DELIVERY
            and order.payment_status in (Order.PaymentStatus.PENDING, Order.PaymentStatus.FAILED)
            and order.status != Order.Status.CANCELLED
        )

    def get_can_confirm_reception(self, order):
        return order.payment_method != Order.PaymentMethod.CASH_ON_DELIVERY and any(
            vo.status == VendorOrder.Status.DELIVERED and vo.received_at is None for vo in order.vendor_orders.all()
        )


class VendorSideOrderSerializer(serializers.ModelSerializer):
    """Sous-commande vue par le vendeur : ses articles + où livrer + actions possibles."""

    order_id = serializers.IntegerField(source="order.id", read_only=True)
    client_name = serializers.CharField(source="order.user.name", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    payment_method = serializers.CharField(source="order.payment_method", read_only=True)
    payment_method_label = serializers.CharField(source="order.get_payment_method_display", read_only=True)
    payment_status = serializers.CharField(source="order.payment_status", read_only=True)
    delivery_method = serializers.CharField(source="order.delivery_method", read_only=True)
    items = OrderItemSerializer(many=True, read_only=True)
    delivery = serializers.SerializerMethodField()
    tracking = serializers.SerializerMethodField()
    next_statuses = serializers.SerializerMethodField()
    net_amount = serializers.SerializerMethodField()

    class Meta:
        model = VendorOrder
        fields = [
            "id", "order_id", "client_name", "subtotal", "delivery_fee", "vendor_discount", "total_amount",
            "net_amount", "status", "status_label", "payment_method", "payment_method_label", "payment_status",
            "delivery_method",
            "items", "delivery", "tracking", "next_statuses", "received_at", "funds_released_at", "created_at",
        ]

    def get_delivery(self, vendor_order):
        data = {f.removeprefix("delivery_"): getattr(vendor_order.order, f) for f in DELIVERY_FIELDS}
        data["name"] = vendor_order.order.delivery_name or vendor_order.order.user.name
        return data

    def get_tracking(self, vendor_order):
        return tracking(vendor_order)

    def get_net_amount(self, vendor_order):
        """Ce que la boutique touchera pour les articles, après sa réduction et la commission."""
        from apps.wallet.services import commission_for

        return vendor_order.subtotal - vendor_order.vendor_discount - commission_for(vendor_order)

    def get_next_statuses(self, vendor_order):
        return VENDOR_ORDER_TRANSITIONS[vendor_order.status]


class VendorOrderStatusSerializer(serializers.Serializer):
    status = serializers.ChoiceField(choices=VendorOrder.Status.choices)
