from django.contrib import admin, messages

from .models import LedgerEntry, PayoutRequest
from .services import process_payout


@admin.register(LedgerEntry)
class LedgerEntryAdmin(admin.ModelAdmin):
    list_display = ["user", "kind", "amount", "vendor_order", "note", "created_at"]
    list_filter = ["kind"]
    search_fields = ["user__email", "user__name", "note"]

    def has_change_permission(self, request, obj=None):
        return False  # un mouvement ne se modifie pas : on ajoute un « ajustement »


@admin.register(PayoutRequest)
class PayoutRequestAdmin(admin.ModelAdmin):
    list_display = ["user", "amount", "method", "phone", "status", "created_at"]
    list_filter = ["status", "method"]
    actions = ["mark_paid", "reject"]
    readonly_fields = ["processed_by", "processed_at"]

    @admin.action(description="Marquer comme payé (transfert effectué)")
    def mark_paid(self, request, queryset):
        for payout in queryset.filter(status=PayoutRequest.Status.PENDING):
            process_payout(payout, request.user, paid=True, reference=payout.transaction_reference)
        messages.success(request, "Retraits marqués comme payés.")

    @admin.action(description="Refuser")
    def reject(self, request, queryset):
        for payout in queryset.filter(status=PayoutRequest.Status.PENDING):
            process_payout(payout, request.user, paid=False, note=payout.admin_note)
        messages.success(request, "Retraits refusés.")
