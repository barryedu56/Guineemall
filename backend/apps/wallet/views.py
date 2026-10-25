from django.db.models import Sum
from rest_framework import mixins, permissions, status, viewsets
from rest_framework.decorators import action
from rest_framework.exceptions import ValidationError
from rest_framework.response import Response

from apps.orders.models import Order, VendorOrder

from .models import LedgerEntry, PayoutRequest
from .serializers import CashDepositSerializer, LedgerEntrySerializer, PayoutRequestSerializer
from .services import declare_deposit, request_payout


class WalletViewSet(viewsets.GenericViewSet):
    """GET /wallet/ : solde, montants bloqués en séquestre et derniers mouvements."""

    permission_classes = [permissions.IsAuthenticated]

    def list(self, request):
        user = request.user
        balance = LedgerEntry.balance(user)
        pending_payouts = (
            user.payouts.filter(status=PayoutRequest.Status.PENDING).aggregate(t=Sum("amount"))["t"] or 0
        )
        escrow = 0
        vendor = getattr(user, "vendor", None)
        if vendor:
            # Ventes payées en ligne, pas encore versées (en cours ou livrées non confirmées)
            escrow = (
                VendorOrder.objects.filter(
                    vendor=vendor, funds_released_at__isnull=True, order__payment_status=Order.PaymentStatus.PAID
                )
                .exclude(status=VendorOrder.Status.CANCELLED)
                .aggregate(t=Sum("subtotal"))["t"]
                or 0
            )
        entries = LedgerEntry.objects.filter(user=user)[:50]
        return Response({
            "balance": balance,
            "available": balance - pending_payouts,
            "pending_payouts": pending_payouts,
            "escrow": escrow,
            "debt": max(0, -balance),
            "entries": LedgerEntrySerializer(entries, many=True).data,
        })


class PayoutViewSet(mixins.ListModelMixin, mixins.CreateModelMixin, viewsets.GenericViewSet):
    serializer_class = PayoutRequestSerializer
    throttle_scope = "money"
    permission_classes = [permissions.IsAuthenticated]
    pagination_class = None

    def get_queryset(self):
        return self.request.user.payouts.all()

    def create(self, request, *args, **kwargs):
        serializer = self.get_serializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        try:
            payout = request_payout(request.user, **serializer.validated_data)
        except ValueError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(payout).data, status=status.HTTP_201_CREATED)


class CashDepositViewSet(mixins.ListModelMixin, mixins.CreateModelMixin, viewsets.GenericViewSet):
    """Versements d'espèces dues à la plateforme (livreurs, boutiques en paiement à la livraison)."""

    serializer_class = CashDepositSerializer
    throttle_scope = "money"
    permission_classes = [permissions.IsAuthenticated]
    pagination_class = None

    def get_queryset(self):
        return self.request.user.cash_deposits.all()

    def create(self, request, *args, **kwargs):
        serializer = self.get_serializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        try:
            deposit = declare_deposit(request.user, **serializer.validated_data)
        except ValueError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(deposit).data, status=status.HTTP_201_CREATED)
