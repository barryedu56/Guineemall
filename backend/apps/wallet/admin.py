from django.contrib import admin, messages

from .models import CashDeposit, LedgerEntry, PayoutRequest
from .services import process_deposit, process_payout


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


@admin.register(CashDeposit)
class CashDepositAdmin(admin.ModelAdmin):
    list_display = ["user", "amount", "method", "reference", "status", "created_at"]
    list_filter = ["status", "method"]
    search_fields = ["user__name", "user__email", "reference"]
    readonly_fields = ["processed_by", "processed_at"]
    actions = ["confirm", "reject"]

    @admin.action(description="Confirmer (argent bien reçu)")
    def confirm(self, request, queryset):
        for deposit in queryset.filter(status=CashDeposit.Status.PENDING):
            process_deposit(deposit, request.user, confirmed=True)
        messages.success(request, "Versements confirmés.")

    @admin.action(description="Refuser (remplir le motif avant)")
    def reject(self, request, queryset):
        for deposit in queryset.filter(status=CashDeposit.Status.PENDING):
            process_deposit(deposit, request.user, confirmed=False, note=deposit.admin_note)
        messages.success(request, "Versements refusés.")
