from django.contrib import admin

from .audit import audit
from .models import AuditLog, SiteSettings


@admin.register(SiteSettings)
class SiteSettingsAdmin(admin.ModelAdmin):
    fieldsets = [
        ("Identité", {"fields": ["site_name", "tagline", "announcement"]}),
        ("Page d'accueil", {"fields": ["hero_title", "hero_subtitle", "hero_image"]}),
        ("Contact", {"fields": ["contact_phone", "contact_whatsapp", "contact_email", "address"]}),
        ("Réseaux sociaux", {"fields": ["facebook_url", "instagram_url", "tiktok_url"]}),
        ("Modes de paiement proposés", {
            "fields": ["enable_orange_money", "enable_mtn_momo", "enable_cash_on_delivery", "enable_card"],
        }),
    ]

    def has_add_permission(self, request):
        return not SiteSettings.objects.exists()

    def save_model(self, request, obj, form, change):
        super().save_model(request, obj, form, change)
        audit(request, "site_settings.changed", obj, {"fields": form.changed_data})

    def has_delete_permission(self, request, obj=None):
        return False


@admin.register(AuditLog)
class AuditLogAdmin(admin.ModelAdmin):
    list_display = ["created_at", "actor", "action", "target", "ip"]
    list_filter = ["action"]
    search_fields = ["target", "actor__email", "actor__name", "ip"]
    readonly_fields = [f.name for f in AuditLog._meta.fields]

    def has_add_permission(self, request):
        return False

    def has_change_permission(self, request, obj=None):
        return False

    def has_delete_permission(self, request, obj=None):
        return False
