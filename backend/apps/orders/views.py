from django.db.models import Prefetch, Q
from rest_framework import mixins, permissions, status, viewsets
from rest_framework.decorators import action
from rest_framework.exceptions import ValidationError
from rest_framework.response import Response

from apps.core.permissions import IsVendeur, get_vendor

from apps.payments.providers import PaymentError
from apps.payments.services import start_payment

from . import services
from .models import CartItem, Order, OrderItem, VendorOrder
from .serializers import (
    CartItemSerializer,
    CheckoutSerializer,
    OrderSerializer,
    PaySerializer,
    QuoteSerializer,
    VendorOrderStatusSerializer,
    VendorSideOrderSerializer,
)


class CartViewSet(viewsets.ModelViewSet):
    """
    GET /cart/ : contenu du panier + total
    POST /cart/ {product_slug, quantity} : ajoute (ou augmente la quantité)
    PATCH /cart/<id>/ {quantity} · DELETE /cart/<id>/ · DELETE /cart/clear/
    """

    serializer_class = CartItemSerializer
    permission_classes = [permissions.IsAuthenticated]
    pagination_class = None
    http_method_names = ["get", "post", "patch", "delete"]

    def get_queryset(self):
        return (
            CartItem.objects.filter(user=self.request.user)
            .select_related("product__vendor", "product__category")
            .prefetch_related("product__images")
        )

    def list(self, request, *args, **kwargs):
        # Articles de sa propre boutique (ajoutés avant d'ouvrir la boutique, par ex.) : retirés
        CartItem.objects.filter(user=request.user, product__vendor__user=request.user).delete()
        items = self.get_serializer(self.get_queryset(), many=True).data
        return Response({"items": items, "total": sum(i["subtotal"] for i in items)})

    def create(self, request, *args, **kwargs):
        serializer = self.get_serializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        product = serializer.validated_data["product"]
        quantity = serializer.validated_data["quantity"]
        item, created = CartItem.objects.get_or_create(
            user=request.user, product=product, defaults={"quantity": quantity}
        )
        if not created:
            if item.quantity + quantity > product.stock:
                raise ValidationError({"quantity": f"Il ne reste que {product.stock} en stock."})
            item.quantity += quantity
            item.save(update_fields=["quantity", "updated_at"])
        return Response(self.get_serializer(item).data, status=status.HTTP_201_CREATED)

    @action(detail=False, methods=["delete"])
    def clear(self, request):
        self.get_queryset().delete()
        return Response(status=status.HTTP_204_NO_CONTENT)


def with_details(queryset):
    return queryset.select_related("coupon", "pickup_point").prefetch_related(
        Prefetch(
            "vendor_orders",
            queryset=VendorOrder.objects.select_related("vendor", "delivery__rider__user"),
        ),
        Prefetch("vendor_orders__items", queryset=OrderItem.objects.select_related("product")),
        "status_history",
    )


class OrderViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, viewsets.GenericViewSet):
    """Commandes du client. POST /orders/ = passer commande avec le panier."""

    serializer_class = OrderSerializer
    permission_classes = [permissions.IsAuthenticated]
    filterset_fields = ["status"]

    def get_queryset(self):
        return with_details(Order.objects.alive().filter(user=self.request.user))

    def create(self, request):
        checkout = CheckoutSerializer(data=request.data, context={"request": request})
        checkout.is_valid(raise_exception=True)
        try:
            order = services.checkout(request.user, **checkout.validated_data)
        except services.OrderError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(self.get_queryset().get(pk=order.pk)).data, status=status.HTTP_201_CREATED)

    @action(detail=False, methods=["post"])
    def quote(self, request):
        """Détail du prix (articles, livraison, réduction) avant de confirmer."""
        payload = QuoteSerializer(data=request.data, context={"request": request})
        payload.is_valid(raise_exception=True)
        cart = CartItem.objects.filter(user=request.user).select_related("product__vendor")
        lines = [(item.product, item.quantity) for item in cart]
        if not lines:
            raise ValidationError({"detail": "Votre panier est vide."})
        try:
            quote = services.build_quote(request.user, lines, **payload.validated_data)
        except services.OrderError as e:
            raise ValidationError({"detail": str(e)})
        return Response({
            "subtotal": quote.subtotal,
            "delivery_fee": quote.delivery_fee,
            "discount": quote.discount,
            "total": quote.total,
            "delivery_delay": quote.delivery_delay,
            "coupon": quote.coupon.code if quote.coupon else None,
            "coupon_error": quote.coupon_error,
            "shops": [
                {"shop_name": v.vendor.shop_name, "subtotal": v.subtotal, "delivery_fee": v.delivery_fee}
                for v in quote.vendors
            ],
        })

    @action(detail=True, methods=["post"])
    def pay(self, request, pk=None):
        """Lance (ou relance) le paiement en ligne. Renvoie l'URL de paiement ou un message."""
        payload = PaySerializer(data=request.data)
        payload.is_valid(raise_exception=True)
        try:
            payment, init = start_payment(self.get_object(), payload.validated_data.get("phone", ""))
        except PaymentError as e:
            raise ValidationError({"detail": str(e)})
        return Response({"reference": str(payment.reference), "redirect_url": init.redirect_url, "message": init.message})

    @action(detail=True, methods=["post"], url_path="confirm-reception")
    def confirm_reception(self, request, pk=None):
        try:
            services.confirm_reception(self.get_object(), request.user)
        except services.OrderError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)

    @action(detail=True, methods=["post"])
    def cancel(self, request, pk=None):
        try:
            services.cancel_order(self.get_object(), request.user)
        except services.OrderError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)


class VendorOrderViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, viewsets.GenericViewSet):
    """Espace vendeur : les parties de commandes qui concernent sa boutique."""

    serializer_class = VendorSideOrderSerializer
    permission_classes = [IsVendeur]
    filterset_fields = ["status"]

    def get_queryset(self):
        # Une commande payable en ligne n'apparaît qu'une fois payée
        return (
            get_vendor(self.request).vendor_orders
            .filter(
                Q(order__payment_method=Order.PaymentMethod.CASH_ON_DELIVERY)
                | Q(order__payment_status__in=[
                    Order.PaymentStatus.PAID, Order.PaymentStatus.REFUND_PENDING, Order.PaymentStatus.REFUNDED,
                ])
            )
            .select_related("order__user", "delivery__rider__user")
            .prefetch_related(Prefetch("items", queryset=OrderItem.objects.select_related("product")))
        )

    @action(detail=True, methods=["post"])
    def status(self, request, pk=None):
        payload = VendorOrderStatusSerializer(data=request.data)
        payload.is_valid(raise_exception=True)
        try:
            services.update_vendor_order_status(self.get_object(), payload.validated_data["status"], request.user)
        except services.OrderError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(self.get_queryset().get(pk=pk)).data)
