from rest_framework import serializers

from apps.accounts.models import Address
from apps.catalog.models import Product
from apps.catalog.serializers import ProductListSerializer

from .models import CartItem, Order, OrderItem, OrderStatusHistory, VendorOrder
from .services import VENDOR_ORDER_TRANSITIONS


class CartItemSerializer(serializers.ModelSerializer):
    product = ProductListSerializer(read_only=True)
    product_slug = serializers.SlugRelatedField(
        source="product", slug_field="slug", write_only=True,
        queryset=Product.objects.alive().filter(status=Product.Status.ACTIVE),
    )
    quantity = serializers.IntegerField(min_value=1)
    subtotal = serializers.IntegerField(read_only=True)

    class Meta:
        model = CartItem
        fields = ["id", "product", "product_slug", "quantity", "subtotal"]

    def validate(self, attrs):
        product = attrs.get("product", getattr(self.instance, "product", None))
        if product and attrs.get("quantity", 1) > product.stock:
            raise serializers.ValidationError({"quantity": f"Il ne reste que {product.stock} en stock."})
        return attrs


class CheckoutSerializer(serializers.Serializer):
    address = serializers.PrimaryKeyRelatedField(queryset=Address.objects.none())
    payment_method = serializers.ChoiceField(choices=Order.PaymentMethod.choices)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Le client ne peut utiliser que ses propres adresses
        self.fields["address"].queryset = self.context["request"].user.addresses.all()


class OrderItemSerializer(serializers.ModelSerializer):
    product_slug = serializers.SlugRelatedField(source="product", slug_field="slug", read_only=True)
    subtotal = serializers.IntegerField(read_only=True)

    class Meta:
        model = OrderItem
        fields = ["id", "product_slug", "product_name", "unit_price", "quantity", "subtotal"]


class VendorOrderSerializer(serializers.ModelSerializer):
    shop_name = serializers.CharField(source="vendor.shop_name", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    items = OrderItemSerializer(many=True, read_only=True)

    class Meta:
        model = VendorOrder
        fields = ["id", "shop_name", "total_amount", "status", "status_label", "items"]


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
    vendor_orders = VendorOrderSerializer(many=True, read_only=True)
    status_history = StatusHistorySerializer(many=True, read_only=True)

    class Meta:
        model = Order
        fields = [
            "id", "total_amount", "status", "status_label",
            "payment_method", "payment_status", *DELIVERY_FIELDS,
            "vendor_orders", "status_history", "created_at",
        ]


class VendorSideOrderSerializer(serializers.ModelSerializer):
    """Sous-commande vue par le vendeur : ses articles + où livrer + actions possibles."""

    order_id = serializers.IntegerField(source="order.id", read_only=True)
    client_name = serializers.CharField(source="order.user.name", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    payment_method = serializers.CharField(source="order.payment_method", read_only=True)
    items = OrderItemSerializer(many=True, read_only=True)
    delivery = serializers.SerializerMethodField()
    next_statuses = serializers.SerializerMethodField()

    class Meta:
        model = VendorOrder
        fields = [
            "id", "order_id", "client_name", "total_amount", "status", "status_label",
            "payment_method", "items", "delivery", "next_statuses", "created_at",
        ]

    def get_delivery(self, vendor_order):
        return {f.removeprefix("delivery_"): getattr(vendor_order.order, f) for f in DELIVERY_FIELDS}

    def get_next_statuses(self, vendor_order):
        return VENDOR_ORDER_TRANSITIONS[vendor_order.status]


class VendorOrderStatusSerializer(serializers.Serializer):
    status = serializers.ChoiceField(choices=VendorOrder.Status.choices)
