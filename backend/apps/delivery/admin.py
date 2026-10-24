from django.contrib import admin

from apps.notifications.services import notify

from .models import Delivery, DeliveryZone, PickupPoint, Rider


@admin.register(DeliveryZone)
class DeliveryZoneAdmin(admin.ModelAdmin):
    list_display = ["city", "commune", "fee", "delay", "is_active"]
    list_editable = ["fee", "delay", "is_active"]
    list_filter = ["city"]


@admin.register(PickupPoint)
class PickupPointAdmin(admin.ModelAdmin):
    list_display = ["name", "city", "commune", "phone", "fee", "is_active"]
    list_filter = ["city", "is_active"]


@admin.register(Rider)
class RiderAdmin(admin.ModelAdmin):
    list_display = ["user", "city", "vehicle_plate", "is_approved", "is_available", "location_updated_at"]
    list_filter = ["is_approved", "city"]
    actions = ["approve"]

    @admin.action(description="Valider les livreurs sélectionnés")
    def approve(self, request, queryset):
        for rider in queryset.filter(is_approved=False):
            rider.is_approved = True
            rider.save(update_fields=["is_approved", "updated_at"])
            notify(rider.user, "delivery", "Compte livreur validé ✅", "Vous pouvez accepter des courses.", "/livreur")


@admin.register(Delivery)
class DeliveryAdmin(admin.ModelAdmin):
    list_display = ["vendor_order", "rider", "status", "fee", "created_at"]
    list_filter = ["status"]
