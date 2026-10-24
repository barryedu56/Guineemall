from django.contrib import admin

from .models import Vendor


@admin.register(Vendor)
class VendorAdmin(admin.ModelAdmin):
    list_display = ["shop_name", "user", "status", "is_verified", "created_at"]
    list_filter = ["status", "is_verified"]
    search_fields = ["shop_name", "user__email"]
