from django.contrib import admin

from .models import Campaign, Coupon, CouponRedemption


@admin.register(Coupon)
class CouponAdmin(admin.ModelAdmin):
    list_display = ["code", "type", "value", "vendor", "user", "used_count", "usage_limit", "ends_at", "is_active"]
    list_filter = ["type", "is_active"]
    search_fields = ["code"]


@admin.register(Campaign)
class CampaignAdmin(admin.ModelAdmin):
    list_display = ["name", "starts_at", "ends_at", "is_active"]
    filter_horizontal = ["products"]
    prepopulated_fields = {"slug": ["name"]}


admin.site.register(CouponRedemption)
