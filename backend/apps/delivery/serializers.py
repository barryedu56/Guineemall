from rest_framework import serializers

from .models import Delivery, DeliveryZone, PickupPoint, Rider


class DeliveryZoneSerializer(serializers.ModelSerializer):
    class Meta:
        model = DeliveryZone
        fields = ["id", "city", "commune", "fee", "delay"]


class PickupPointSerializer(serializers.ModelSerializer):
    class Meta:
        model = PickupPoint
        fields = ["id", "name", "city", "commune", "address", "phone", "opening_hours", "fee", "latitude", "longitude"]


class RiderSerializer(serializers.ModelSerializer):
    rating = serializers.SerializerMethodField()
    cash_debt = serializers.SerializerMethodField()
    cash_limit = serializers.SerializerMethodField()

    class Meta:
        model = Rider
        fields = ["city", "communes", "vehicle_plate", "is_approved", "is_available", "rating", "cash_debt", "cash_limit"]
        read_only_fields = ["is_approved"]

    def get_rating(self, rider):
        from .services import rider_rating

        return rider_rating(rider)

    def get_cash_debt(self, rider):
        from .services import cash_debt

        return cash_debt(rider.user)

    def get_cash_limit(self, rider):
        from django.conf import settings

        return settings.RIDER_CASH_LIMIT


class RiderDeliverySerializer(serializers.ModelSerializer):
    """Course vue par le livreur : où récupérer, où livrer, combien encaisser."""

    status_label = serializers.CharField(source="get_status_display", read_only=True)
    order_id = serializers.IntegerField(source="vendor_order.order_id", read_only=True)
    pickup = serializers.SerializerMethodField()
    dropoff = serializers.SerializerMethodField()
    cash_to_collect = serializers.SerializerMethodField()
    items_count = serializers.SerializerMethodField()

    class Meta:
        model = Delivery
        fields = [
            "id", "order_id", "status", "status_label", "fee", "cash_to_collect", "items_count",
            "pickup", "dropoff", "created_at",
        ]

    def get_pickup(self, delivery):
        vendor = delivery.vendor_order.vendor
        return {"shop_name": vendor.shop_name, "phone": vendor.whatsapp or vendor.user.phone}

    def get_dropoff(self, delivery):
        order = delivery.vendor_order.order
        hide = delivery.status == Delivery.Status.OPEN  # coordonnées du client visibles une fois la course acceptée
        return {
            "city": order.delivery_city,
            "commune": order.delivery_commune,
            "quartier": order.delivery_quartier,
            "landmark": None if hide else order.delivery_landmark,
            "phone": None if hide else order.delivery_phone,
            "name": None if hide else (order.delivery_name or order.user.name),
            "latitude": None if hide else order.delivery_latitude,
            "longitude": None if hide else order.delivery_longitude,
        }

    def get_cash_to_collect(self, delivery):
        from .services import cash_to_collect

        return cash_to_collect(delivery)

    def get_items_count(self, delivery):
        return sum(i.quantity for i in delivery.vendor_order.items.all())
