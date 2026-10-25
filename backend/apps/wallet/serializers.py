from rest_framework import serializers

from .models import CashDeposit, CreditEntry, CreditRefund, LedgerEntry, PayoutAccount, PayoutRequest


class LedgerEntrySerializer(serializers.ModelSerializer):
    kind_label = serializers.CharField(source="get_kind_display", read_only=True)

    class Meta:
        model = LedgerEntry
        fields = ["id", "kind", "kind_label", "amount", "note", "created_at"]


class PayoutRequestSerializer(serializers.ModelSerializer):
    status_label = serializers.CharField(source="get_status_display", read_only=True)

    class Meta:
        model = PayoutRequest
        fields = ["id", "amount", "method", "phone", "status", "status_label", "admin_note", "created_at", "processed_at"]
        # Le numéro vient du compte de retrait vérifié, jamais de la demande
        read_only_fields = ["method", "phone", "status", "admin_note", "processed_at"]


class CashDepositSerializer(serializers.ModelSerializer):
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    method_label = serializers.CharField(source="get_method_display", read_only=True)

    class Meta:
        model = CashDeposit
        fields = ["id", "amount", "method", "method_label", "reference", "status", "status_label", "admin_note", "created_at"]
        read_only_fields = ["status", "admin_note"]


class CreditEntrySerializer(serializers.ModelSerializer):
    kind_label = serializers.CharField(source="get_kind_display", read_only=True)

    class Meta:
        model = CreditEntry
        fields = ["id", "kind", "kind_label", "amount", "order", "note", "created_at"]


class CreditRefundSerializer(serializers.ModelSerializer):
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    method_label = serializers.CharField(source="get_method_display", read_only=True)

    class Meta:
        model = CreditRefund
        fields = ["id", "amount", "method", "method_label", "phone", "status", "status_label", "admin_note", "created_at", "processed_at"]
        read_only_fields = ["method", "phone", "status", "admin_note", "processed_at"]


class PayoutAccountSerializer(serializers.ModelSerializer):
    method_label = serializers.CharField(source="get_method_display", read_only=True)
    status_label = serializers.CharField(source="get_status_display", read_only=True)
    password = serializers.CharField(write_only=True, help_text="Mot de passe du compte, redemandé par sécurité")
    ready = serializers.SerializerMethodField()

    class Meta:
        model = PayoutAccount
        fields = ["method", "method_label", "phone", "holder_name", "status", "status_label", "admin_note", "hold_until",
                  "ready", "password"]
        read_only_fields = ["status", "admin_note", "hold_until"]

    def get_ready(self, account):
        """Vérifié et hors délai de sécurité : les retraits sont possibles."""
        from django.utils import timezone

        return account.status == PayoutAccount.Status.VERIFIED and not (account.hold_until and account.hold_until > timezone.now())
