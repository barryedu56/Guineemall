from django.contrib import admin

from .models import Payment


@admin.register(Payment)
class PaymentAdmin(admin.ModelAdmin):
    list_display = ["reference", "order", "provider", "amount", "status", "created_at"]
    list_filter = ["provider", "status"]
    search_fields = ["reference", "provider_reference", "order__id"]
    readonly_fields = [f.name for f in Payment._meta.fields]
