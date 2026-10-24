from django.contrib import admin

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
    list_display = ["order", "vendor", "total_amount", "status"]
    list_filter = ["status"]


admin.site.register(OrderItem)
admin.site.register(CartItem)
