from django.contrib import admin

from apps.backoffice.services import set_rider_approval

from .models import Delivery, DeliveryRating, DeliveryZone, PickupPoint, Rider


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
    list_display = ["user", "city", "vehicle_plate", "is_approved", "is_available", "note_moyenne", "especes_dues"]

    @admin.display(description="note")
    def note_moyenne(self, rider):
        from .services import rider_rating

        data = rider_rating(rider)
        return f"{data['rating']} ★ ({data['ratings_count']})" if data["rating"] else "—"

    @admin.display(description="espèces dues")
    def especes_dues(self, rider):
        from apps.core.formatting import gnf

        from .services import cash_debt

        return gnf(cash_debt(rider.user))
    list_filter = ["is_approved", "city"]
    actions = ["approve"]

    @admin.action(description="Valider les livreurs sélectionnés")
    def approve(self, request, queryset):
        for rider in queryset:
            set_rider_approval(request.user, rider, True)


@admin.register(Delivery)
class DeliveryAdmin(admin.ModelAdmin):
    list_display = ["vendor_order", "rider", "status", "fee", "created_at"]
    list_filter = ["status"]


@admin.register(DeliveryRating)
class DeliveryRatingAdmin(admin.ModelAdmin):
    list_display = ["delivery", "rating", "comment", "created_at"]
    list_filter = ["rating"]
