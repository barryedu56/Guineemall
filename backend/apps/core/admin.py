from django.contrib import admin

from .models import SiteSettings


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

    def has_delete_permission(self, request, obj=None):
        return False
