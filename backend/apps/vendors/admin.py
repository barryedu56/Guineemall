from django.contrib import admin, messages
from django.urls import reverse
from django.utils.html import format_html_join

from apps.backoffice.services import approve_vendor, decide_verification, suspend_vendor

from .models import Vendor, VerificationRequest


@admin.register(Vendor)
class VendorAdmin(admin.ModelAdmin):
    list_display = ["shop_name", "user", "status", "is_verified", "is_premium", "created_at"]
    list_filter = ["status", "is_verified", "is_premium"]
    search_fields = ["shop_name", "user__email"]
    actions = ["approve", "suspend"]

    @admin.action(description="Approuver les boutiques sélectionnées")
    def approve(self, request, queryset):
        for vendor in queryset:
            approve_vendor(request.user, vendor)
        messages.success(request, "Boutiques approuvées.")

    @admin.action(description="Suspendre les boutiques sélectionnées")
    def suspend(self, request, queryset):
        for vendor in queryset:
            suspend_vendor(request.user, vendor)


@admin.register(VerificationRequest)
class VerificationRequestAdmin(admin.ModelAdmin):
    list_display = ["vendor", "document_type", "document_number", "status", "created_at"]
    list_filter = ["status", "document_type"]
    readonly_fields = ["preview", "reviewed_by", "reviewed_at"]
    actions = ["accept", "reject"]

    @admin.display(description="Documents")
    def preview(self, obj):
        # Servis par une vue réservée à l'équipe (stockage privé, pas d'adresse publique)
        fields = [f for f in ("document_front", "document_back", "shop_photo") if getattr(obj, f)]
        return format_html_join(
            "", '<a href="{0}" target="_blank"><img src="{0}" style="height:160px;margin-right:8px"></a>',
            ((reverse("private-verification-file", args=[obj.pk, f]),) for f in fields),
        )

    def _decide(self, request, queryset, approved):
        for vr in queryset:
            decide_verification(request.user, vr, approved)

    @admin.action(description="Accepter (donner le badge)")
    def accept(self, request, queryset):
        self._decide(request, queryset, True)

    @admin.action(description="Refuser (remplir le motif avant)")
    def reject(self, request, queryset):
        self._decide(request, queryset, False)
