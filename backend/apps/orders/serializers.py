from rest_framework import serializers

from apps.accounts.models import Address
from apps.core.phone import PhoneSerializerField
from apps.delivery.models import PickupPoint
from apps.catalog.models import Product
from apps.catalog.serializers import ProductListSerializer

from .models import CartItem, Order, OrderItem, OrderStatusHistory, VendorOrder
from . import services
from .services import VENDOR_ORDER_TRANSITIONS
from .timeline import vendor_order_timeline


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
    use_credit = serializers.BooleanField(required=False, default=False)

    def validate_payment_method(self, method):
        from apps.core.models import SiteSettings

        if method not in SiteSettings.load().enabled_payment_methods():
            raise serializers.ValidationError("Ce mode de paiement n'est pas disponible pour le moment.")
        return method


class PaySerializer(serializers.Serializer):
    phone = PhoneSerializerField(required=False, allow_blank=True)


def first_image(product):
    """Photo principale du produit commandé (images préchargées avec la commande)."""
    images = list(product.images.all()) if product else []
    return images[0].image.url if images else None


class OrderItemSerializer(serializers.ModelSerializer):
    product_slug = serializers.SlugRelatedField(source="product", slug_field="slug", read_only=True)
    subtotal = serializers.IntegerField(read_only=True)
    product_image = serializers.SerializerMethodField()

    class Meta:
        model = OrderItem
        fields = ["id", "product_slug", "product_name", "product_image", "unit_price", "quantity", "subtotal"]

    def get_product_image(self, item):
        return first_image(item.product)


def tracking(vendor_order, with_rating=False):
    """Suivi du livreur, s'il y en a un (avec sa note moyenne et celle laissée par le client)."""
    from apps.delivery.models import DeliveryRating
    from apps.delivery.services import rider_rating

    delivery = getattr(vendor_order, "delivery", None)
    if delivery is None or delivery.status == "cancelled":
        return None
    # Le livreur n'est présenté au client qu'une fois qu'il a accepté la course
    rider = delivery.rider if delivery.status in ("assigned", "picked_up", "delivered") else None
    extra = {}
    if with_rating and rider:
        given = DeliveryRating.objects.filter(delivery=delivery).values_list("rating", flat=True).first()
        extra = {"rider_rating": rider_rating(rider)["rating"], "my_rating": given}
    return {
        **extra,
        "delivery_id": delivery.id,
        "status": delivery.status,
        "status_label": delivery.get_status_display() if rider else "La boutique choisit votre livreur",
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
    delivery_code = serializers.SerializerMethodField()
    timeline = serializers.SerializerMethodField()

    class Meta:
        model = VendorOrder
        fields = [
            "id", "shop_name", "shop_slug", "subtotal", "delivery_fee", "vendor_discount", "platform_discount",
            "total_amount", "status", "status_label", "received_at", "items", "tracking", "delivery_code", "timeline",
        ]

    def get_timeline(self, vendor_order):
        return vendor_order_timeline(vendor_order)

    def get_tracking(self, vendor_order):
        return tracking(vendor_order, with_rating=True)

    def get_delivery_code(self, vendor_order):
        # Uniquement pour le client, et seulement tant que le colis n'est pas remis
        return vendor_order.delivery_code if vendor_order.status == VendorOrder.Status.READY else None


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
    amount_due = serializers.IntegerField(read_only=True)
    can_confirm_reception = serializers.SerializerMethodField()

    class Meta:
        model = Order
        fields = [
            "id", "subtotal", "delivery_fee", "discount_amount", "total_amount", "credit_used", "amount_due",
            "refunded_amount", "coupon_code",
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
        return order.payment_method in services.ONLINE_METHODS and any(
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
    pickup_point = serializers.SerializerMethodField()
    courier = serializers.SerializerMethodField()
    timeline = serializers.SerializerMethodField()
    next_statuses = serializers.SerializerMethodField()
    net_amount = serializers.SerializerMethodField()
    cash_to_collect = serializers.SerializerMethodField()

    class Meta:
        model = VendorOrder
        fields = [
            "id", "order_id", "client_name", "subtotal", "delivery_fee", "vendor_discount", "total_amount",
            "net_amount", "cash_to_collect", "status", "status_label", "payment_method", "payment_method_label",
            "payment_status", "delivery_method", "pickup_point",
            "items", "delivery", "courier", "timeline", "next_statuses", "received_at", "funds_released_at", "created_at",
        ]

    def get_delivery(self, vendor_order):
        data = {f.removeprefix("delivery_"): getattr(vendor_order.order, f) for f in DELIVERY_FIELDS}
        data["name"] = vendor_order.order.delivery_name or vendor_order.order.user.name
        return data

    def get_pickup_point(self, vendor_order):
        point = vendor_order.order.pickup_point
        return point and {"name": point.name, "address": point.address, "commune": point.commune,
                          "phone": point.phone, "opening_hours": point.opening_hours}

    def get_courier(self, vendor_order):
        from apps.delivery.serializers import delivery_for_vendor

        delivery = getattr(vendor_order, "delivery", None)
        if delivery is not None and delivery.status == "cancelled":
            delivery = None
        return delivery_for_vendor(delivery, self.context["request"].user)

    def get_timeline(self, vendor_order):
        return vendor_order_timeline(vendor_order)

    def get_cash_to_collect(self, vendor_order):
        cash = vendor_order.order.payment_method == Order.PaymentMethod.CASH_ON_DELIVERY
        return vendor_order.total_amount if cash and vendor_order.status not in ("delivered", "cancelled") else 0

    def get_net_amount(self, vendor_order):
        """Ce que la boutique touchera pour les articles, après sa réduction et la commission."""
        from apps.wallet.services import commission_for

        return vendor_order.subtotal - vendor_order.vendor_discount - commission_for(vendor_order)

    def get_next_statuses(self, vendor_order):
        return VENDOR_ORDER_TRANSITIONS[vendor_order.status]


class AssignRiderSerializer(serializers.Serializer):
    rider = serializers.IntegerField()


class VendorOrderStatusSerializer(serializers.Serializer):
    status = serializers.ChoiceField(choices=VendorOrder.Status.choices)
    code = serializers.CharField(required=False, allow_blank=True, max_length=4)
