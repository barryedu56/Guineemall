from django.contrib import admin, messages
from django.utils import timezone
from django.utils.html import format_html

from apps.notifications.services import notify

from .models import Vendor, VerificationRequest


@admin.register(Vendor)
class VendorAdmin(admin.ModelAdmin):
    list_display = ["shop_name", "user", "status", "is_verified", "is_premium", "created_at"]
    list_filter = ["status", "is_verified", "is_premium"]
    search_fields = ["shop_name", "user__email"]
    actions = ["approve", "suspend"]

    @admin.action(description="Approuver les boutiques sélectionnées")
    def approve(self, request, queryset):
        for vendor in queryset.exclude(status=Vendor.Status.APPROVED):
            vendor.status = Vendor.Status.APPROVED
            vendor.save(update_fields=["status", "updated_at"])
            notify(vendor.user, "shop", "Votre boutique est en ligne 🎉",
                   "Ajoutez vos produits et publiez votre premier statut.", "/vendeur")
        messages.success(request, "Boutiques approuvées.")

    @admin.action(description="Suspendre les boutiques sélectionnées")
    def suspend(self, request, queryset):
        for vendor in queryset.exclude(status=Vendor.Status.SUSPENDED):
            vendor.status = Vendor.Status.SUSPENDED
            vendor.save(update_fields=["status", "updated_at"])
            notify(vendor.user, "shop", "Boutique suspendue", "Contactez GuinéeMall pour en savoir plus.", "/vendeur")


@admin.register(VerificationRequest)
class VerificationRequestAdmin(admin.ModelAdmin):
    list_display = ["vendor", "document_type", "document_number", "status", "created_at"]
    list_filter = ["status", "document_type"]
    readonly_fields = ["preview", "reviewed_by", "reviewed_at"]
    actions = ["accept", "reject"]

    @admin.display(description="Documents")
    def preview(self, obj):
        images = [f for f in (obj.document_front, obj.document_back, obj.shop_photo) if f]
        return format_html(
            "".join('<a href="{}" target="_blank"><img src="{}" style="height:160px;margin-right:8px"></a>' for _ in images),
            *[url for f in images for url in (f.url, f.url)],
        )

    def _decide(self, request, queryset, approved):
        for vr in queryset.filter(status=VerificationRequest.Status.PENDING).select_related("vendor__user"):
            vr.status = VerificationRequest.Status.APPROVED if approved else VerificationRequest.Status.REJECTED
            vr.reviewed_by = request.user
            vr.reviewed_at = timezone.now()
            vr.save()
            if approved:
                vr.vendor.is_verified = True
                vr.vendor.verified_at = timezone.now()
                vr.vendor.save(update_fields=["is_verified", "verified_at", "updated_at"])
                notify(vr.vendor.user, "shop", "Badge « Vendeur vérifié » obtenu ✅",
                       "Le badge apparaît maintenant sur votre boutique et vos produits.", "/vendeur/boutique")
            else:
                notify(vr.vendor.user, "shop", "Vérification refusée",
                       vr.admin_note or "Vos documents n'ont pas pu être validés. Vous pouvez renvoyer une demande.",
                       "/vendeur/boutique")

    @admin.action(description="Accepter (donner le badge)")
    def accept(self, request, queryset):
        self._decide(request, queryset, True)

    @admin.action(description="Refuser (remplir le motif avant)")
    def reject(self, request, queryset):
        self._decide(request, queryset, False)
