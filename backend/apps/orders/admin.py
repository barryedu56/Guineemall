from django.contrib import admin

from apps.core.audit import audit

from .models import CartItem, Order, OrderItem, OrderStatusHistory, VendorOrder


class VendorOrderInline(admin.TabularInline):
    model = VendorOrder
    extra = 0


class OrderStatusHistoryInline(admin.TabularInline):
    model = OrderStatusHistory
    extra = 0


@admin.register(Order)
class OrderAdmin(admin.ModelAdmin):
    list_display = ["id", "user", "total_amount", "status", "payment_method", "payment_status", "created_at"]
    list_filter = ["status", "payment_method", "payment_status"]
    inlines = [VendorOrderInline, OrderStatusHistoryInline]


@admin.register(VendorOrder)
class VendorOrderAdmin(admin.ModelAdmin):
    list_display = ["order", "vendor", "total_amount", "status", "delivery_code_attempts"]
    list_filter = ["status"]
    actions = ["unlock_code"]

    @admin.action(description="Débloquer le code de livraison (après vérification)")
    def unlock_code(self, request, queryset):
        for vendor_order in queryset:
            audit(request, "delivery_code.unlocked", vendor_order, {"attempts": vendor_order.delivery_code_attempts})
        queryset.update(delivery_code_attempts=0)


admin.site.register(OrderItem)
admin.site.register(CartItem)
