from rest_framework import serializers

from apps.accounts.models import User
from apps.catalog.models import Category, Product, Review
from apps.core.models import AuditLog, SiteSettings
from apps.delivery.models import DeliveryZone, PickupPoint, Rider
from apps.orders.models import Order, VendorOrder
from apps.promotions.models import Campaign, Coupon
from apps.vendors.models import Vendor, VerificationRequest
from apps.wallet.models import CashDeposit, CreditEntry, CreditRefund, PayoutAccount, PayoutRequest


class UserMiniSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = ["id", "name", "email", "phone"]


class AdminUserSerializer(serializers.ModelSerializer):
    role_label = serializers.CharField(source="get_role_display", read_only=True)
    has_2fa = serializers.BooleanField(read_only=True)
    shop = serializers.CharField(source="vendor.shop_name", read_only=True, default=None)

    class Meta:
        model = User
        fields = ["id", "name", "email", "phone", "role", "role_label", "is_active", "has_2fa", "shop", "date_joined", "last_login"]


class AdminVendorSerializer(serializers.ModelSerializer):
    owner = UserMiniSerializer(source="user", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    products_count = serializers.IntegerField(read_only=True)
    orders_count = serializers.IntegerField(read_only=True)
    revenue = serializers.IntegerField(read_only=True)

    class Meta:
        model = Vendor
        fields = [
            "id", "slug", "shop_name", "description", "logo", "whatsapp", "owner", "status", "status_label",
            "is_verified", "is_premium", "commission_rate", "products_count", "orders_count", "revenue", "created_at",
        ]
        # Statut et badge : seulement par les actions dédiées (notification + journal)
        read_only_fields = ["slug", "shop_name", "description", "logo", "whatsapp", "status", "is_verified", "created_at"]

    def validate_commission_rate(self, rate):
        if rate is not None and not (0 <= rate <= 0.5):
            raise serializers.ValidationError("Entre 0 et 0,5 (50 %).")
        return rate


class AdminVerificationSerializer(serializers.ModelSerializer):
    shop_name = serializers.CharField(source="vendor.shop_name", read_only=True)
    shop_slug = serializers.CharField(source="vendor.slug", read_only=True)
    owner = UserMiniSerializer(source="vendor.user", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    document_type_label = serializers.CharField(source="get_document_type_display", read_only=True)
    documents = serializers.SerializerMethodField()

    class Meta:
        model = VerificationRequest
        fields = [
            "id", "shop_name", "shop_slug", "owner", "document_type", "document_type_label", "document_number",
            "status", "status_label", "admin_note", "documents", "created_at", "reviewed_at",
        ]

    def get_documents(self, vr):
        # Noms des pièces disponibles : le fichier se lit via l'API protégée (jamais d'adresse publique)
        return [f for f in ("document_front", "document_back", "shop_photo") if getattr(vr, f)]


class AdminRiderSerializer(serializers.ModelSerializer):
    user = UserMiniSerializer(read_only=True)
    rating = serializers.SerializerMethodField()
    cash_debt = serializers.SerializerMethodField()
    deliveries_done = serializers.IntegerField(read_only=True)

    class Meta:
        model = Rider
        fields = [
            "id", "user", "city", "communes", "vehicle_plate", "is_approved", "is_available",
            "rating", "cash_debt", "deliveries_done", "created_at",
        ]
        read_only_fields = ["is_approved", "is_available"]

    def get_rating(self, rider):
        from apps.delivery.services import rider_rating

        return rider_rating(rider)

    def get_cash_debt(self, rider):
        from apps.delivery.services import cash_debt

        return cash_debt(rider.user)


class AdminVendorOrderSerializer(serializers.ModelSerializer):
    shop_name = serializers.CharField(source="vendor.shop_name", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    items = serializers.SerializerMethodField()
    code_blocked = serializers.SerializerMethodField()
    rider = serializers.SerializerMethodField()
    shop_phone = serializers.CharField(source="vendor.contact_phone", read_only=True)
    client_report_label = serializers.CharField(source="get_client_report_display", read_only=True)
    code_attempts = serializers.SerializerMethodField()
    contested = serializers.SerializerMethodField()

    class Meta:
        model = VendorOrder
        fields = [
            "id", "shop_name", "shop_phone", "subtotal", "delivery_fee", "vendor_discount", "platform_discount", "total_amount",
            "status", "status_label", "delivery_code_attempts", "code_blocked", "contested", "client_report",
            "client_report_label", "client_report_at", "confirmation_requested_at", "contest_until",
            "code_attempts", "delivered_at", "received_at", "funds_released_at", "items", "rider",
        ]

    def get_code_attempts(self, vo):
        """Qui a saisi quel type de code, et quand (le code lui-même n'est jamais stocké)."""
        return [
            {"by": a.user.name if a.user else None, "role": a.user.get_role_display() if a.user else None,
             "success": a.success, "at": a.created_at}
            for a in vo.code_attempts.all()
        ]

    def get_items(self, vo):
        return [{"name": i.product_name, "quantity": i.quantity, "unit_price": i.unit_price} for i in vo.items.all()]

    def get_code_blocked(self, vo):
        from apps.orders.disputes import is_blocked

        return is_blocked(vo)

    def get_contested(self, vo):
        from apps.orders.disputes import is_contested

        return is_contested(vo)

    def get_rider(self, vo):
        delivery = getattr(vo, "delivery", None)
        if delivery is None or delivery.rider is None:
            return None
        return {"name": delivery.rider.user.name, "phone": delivery.rider.user.phone, "status": delivery.get_status_display()}


class AdminOrderSerializer(serializers.ModelSerializer):
    client = UserMiniSerializer(source="user", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    payment_method_label = serializers.CharField(source="get_payment_method_display", read_only=True)
    payment_status_label = serializers.CharField(source="get_payment_status_display", read_only=True)
    shops = serializers.SerializerMethodField()

    class Meta:
        model = Order
        fields = [
            "id", "client", "subtotal", "delivery_fee", "discount_amount", "total_amount", "credit_used", "refunded_amount",
            "status", "status_label",
            "payment_method", "payment_method_label", "payment_status", "payment_status_label", "delivery_method",
            "delivery_city", "delivery_commune", "delivery_quartier", "delivery_landmark", "delivery_phone",
            "delivery_name", "shops", "created_at",
        ]

    def get_shops(self, order):
        return [vo.vendor.shop_name for vo in order.vendor_orders.all()]


class AdminOrderDetailSerializer(AdminOrderSerializer):
    vendor_orders = AdminVendorOrderSerializer(many=True, read_only=True)
    history = serializers.SerializerMethodField()

    class Meta(AdminOrderSerializer.Meta):
        fields = AdminOrderSerializer.Meta.fields + ["vendor_orders", "history"]

    def get_history(self, order):
        return [
            {"status": h.status, "note": h.note, "by": h.changed_by.name if h.changed_by else None, "at": h.created_at}
            for h in order.status_history.select_related("changed_by")
        ]


class AdminPayoutSerializer(serializers.ModelSerializer):
    user = UserMiniSerializer(read_only=True)
    method_label = serializers.CharField(source="get_method_display", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    balance = serializers.SerializerMethodField()

    class Meta:
        model = PayoutRequest
        fields = [
            "id", "user", "amount", "method", "method_label", "phone", "status", "status_label",
            "transaction_reference", "admin_note", "balance", "created_at", "processed_at",
        ]

    def get_balance(self, payout):
        from apps.wallet.models import LedgerEntry

        return LedgerEntry.balance(payout.user)


class AdminPayoutAccountSerializer(serializers.ModelSerializer):
    user = UserMiniSerializer(read_only=True)
    role_label = serializers.CharField(source="user.get_role_display", read_only=True)
    method_label = serializers.CharField(source="get_method_display", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)

    class Meta:
        model = PayoutAccount
        fields = ["id", "user", "role_label", "method", "method_label", "phone", "holder_name", "status", "status_label",
                  "admin_note", "hold_until", "updated_at", "verified_at"]


class AdminCreditRefundSerializer(serializers.ModelSerializer):
    user = UserMiniSerializer(read_only=True)
    method_label = serializers.CharField(source="get_method_display", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    credit_balance = serializers.SerializerMethodField()

    class Meta:
        model = CreditRefund
        fields = [
            "id", "user", "amount", "method", "method_label", "phone", "status", "status_label",
            "transaction_reference", "admin_note", "credit_balance", "created_at", "processed_at",
        ]

    def get_credit_balance(self, refund):
        return CreditEntry.balance(refund.user)


class AdminDepositSerializer(serializers.ModelSerializer):
    user = UserMiniSerializer(read_only=True)
    method_label = serializers.CharField(source="get_method_display", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)

    class Meta:
        model = CashDeposit
        fields = ["id", "user", "amount", "method", "method_label", "reference", "status", "status_label", "admin_note", "created_at"]


class AdminCategorySerializer(serializers.ModelSerializer):
    products_count = serializers.IntegerField(read_only=True)

    class Meta:
        model = Category
        fields = ["id", "name", "slug", "description", "image", "parent", "status", "products_count"]
        read_only_fields = ["slug"]


class AdminProductSerializer(serializers.ModelSerializer):
    shop_name = serializers.CharField(source="vendor.shop_name", read_only=True)
    category_name = serializers.CharField(source="category.name", read_only=True)
    image = serializers.SerializerMethodField()

    class Meta:
        model = Product
        fields = ["id", "slug", "name", "shop_name", "category_name", "price", "stock", "status", "image", "created_at"]
        read_only_fields = ["slug", "name", "price", "stock", "created_at"]

    def get_image(self, product):
        image = product.images.first()
        return image.image.url if image else None


class AdminReviewSerializer(serializers.ModelSerializer):
    author = serializers.CharField(source="user.name", read_only=True)
    product_name = serializers.CharField(source="product.name", read_only=True)
    product_slug = serializers.CharField(source="product.slug", read_only=True)

    class Meta:
        model = Review
        fields = ["id", "author", "product_name", "product_slug", "rating", "comment", "vendor_reply", "is_visible", "created_at"]
        read_only_fields = ["rating", "comment", "vendor_reply", "created_at"]


class AdminCouponSerializer(serializers.ModelSerializer):
    shop_name = serializers.CharField(source="vendor.shop_name", read_only=True, default=None)
    type_label = serializers.CharField(source="get_type_display", read_only=True)

    class Meta:
        model = Coupon
        fields = [
            "id", "code", "type", "type_label", "value", "max_discount", "min_amount", "shop_name", "starts_at", "ends_at",
            "usage_limit", "per_user_limit", "used_count", "is_active", "description",
        ]
        read_only_fields = ["used_count"]

    def validate_code(self, code):
        code = code.upper().strip()
        if Coupon.objects.filter(code=code).exclude(pk=getattr(self.instance, "pk", None)).exists():
            raise serializers.ValidationError("Ce code existe déjà.")
        return code

    def validate(self, attrs):
        kind = attrs.get("type", getattr(self.instance, "type", None))
        if kind == Coupon.Type.PERCENT and attrs.get("value", getattr(self.instance, "value", 0)) > 90:
            raise serializers.ValidationError({"value": "90 % maximum."})
        return attrs


class AdminCampaignSerializer(serializers.ModelSerializer):
    product_slugs = serializers.SlugRelatedField(
        source="products", slug_field="slug", many=True, required=False,
        queryset=Product.objects.alive().filter(status=Product.Status.ACTIVE),
    )
    coupon_code = serializers.SlugRelatedField(
        source="coupon", slug_field="code", queryset=Coupon.objects.all(), required=False, allow_null=True
    )
    is_running = serializers.BooleanField(read_only=True)

    class Meta:
        model = Campaign
        fields = [
            "id", "name", "slug", "description", "banner", "color", "starts_at", "ends_at", "product_slugs",
            "coupon_code", "is_active", "is_running",
        ]
        read_only_fields = ["slug"]

    def validate(self, attrs):
        start = attrs.get("starts_at", getattr(self.instance, "starts_at", None))
        end = attrs.get("ends_at", getattr(self.instance, "ends_at", None))
        if start and end and end <= start:
            raise serializers.ValidationError({"ends_at": "La fin doit être après le début."})
        return attrs


class AdminZoneSerializer(serializers.ModelSerializer):
    class Meta:
        model = DeliveryZone
        fields = ["id", "city", "commune", "fee", "delay", "is_active"]


class AdminPickupPointSerializer(serializers.ModelSerializer):
    class Meta:
        model = PickupPoint
        fields = ["id", "name", "city", "commune", "address", "phone", "opening_hours", "fee", "is_active"]


class AdminSettingsSerializer(serializers.ModelSerializer):
    class Meta:
        model = SiteSettings
        exclude = ["id"]


class AuditLogSerializer(serializers.ModelSerializer):
    actor = serializers.CharField(source="actor.name", read_only=True, default=None)

    class Meta:
        model = AuditLog
        fields = ["id", "actor", "action", "target", "data", "ip", "created_at"]
