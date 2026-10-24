from django.contrib import admin

from .models import Statut, StatutView


@admin.register(Statut)
class StatutAdmin(admin.ModelAdmin):
    list_display = ["vendor", "media_type", "product", "created_at", "expires_at", "is_active"]
    list_filter = ["media_type"]


admin.site.register(StatutView)
