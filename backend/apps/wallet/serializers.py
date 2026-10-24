from rest_framework import serializers

from .models import LedgerEntry, PayoutRequest


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
        read_only_fields = ["status", "admin_note", "processed_at"]
