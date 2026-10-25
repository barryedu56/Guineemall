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
    list_display = ["order", "vendor", "total_amount", "status", "delivery_code_attempts"]
    list_filter = ["status"]
    # Livraison bloquée : décision dans le back-office (/administration/commandes), avec motif et alertes
    readonly_fields = ["delivery_code", "delivery_code_attempts", "client_report", "client_report_at"]


admin.site.register(OrderItem)
admin.site.register(CartItem)
