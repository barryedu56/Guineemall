from django.core.exceptions import ValidationError as DjangoValidationError
from rest_framework import serializers

from apps.core.uploads import check_upload

from .models import Delivery, DeliveryMessage, DeliveryZone, PickupPoint, Rider


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


def avatar_url(user):
    return user.avatar.url if getattr(user, "avatar", None) else None


def unread_for(delivery, user):
    if not delivery.rider_id:
        return 0
    return delivery.messages.filter(rider_id=delivery.rider_id, read_at__isnull=True).exclude(sender=user).count()


def pickup_of(vendor):
    """Où le livreur récupère le colis."""
    return {
        "shop_name": vendor.shop_name,
        "logo": vendor.logo.url if vendor.logo else None,
        "phone": vendor.contact_phone,
        "whatsapp": vendor.whatsapp or vendor.contact_phone,
        "city": vendor.pickup_city,
        "commune": vendor.pickup_commune,
        "quartier": vendor.pickup_quartier,
        "landmark": vendor.pickup_landmark,
        "hours": vendor.pickup_hours,
        "latitude": vendor.pickup_latitude,
        "longitude": vendor.pickup_longitude,
    }


class RiderDeliverySerializer(serializers.ModelSerializer):
    """Course vue par le livreur : où récupérer, où livrer, combien encaisser."""

    status_label = serializers.CharField(source="get_status_display", read_only=True)
    order_id = serializers.IntegerField(source="vendor_order.order_id", read_only=True)
    pickup = serializers.SerializerMethodField()
    dropoff = serializers.SerializerMethodField()
    cash_to_collect = serializers.SerializerMethodField()
    cash_for_shop = serializers.SerializerMethodField()
    items = serializers.SerializerMethodField()
    items_count = serializers.SerializerMethodField()
    offer_expires_at = serializers.SerializerMethodField()
    timeline = serializers.SerializerMethodField()
    unread = serializers.SerializerMethodField()

    class Meta:
        model = Delivery
        fields = [
            "id", "order_id", "status", "status_label", "fee", "cash_to_collect", "cash_for_shop", "cash_handed_at",
            "items", "items_count",
            "pickup", "dropoff", "offer_expires_at", "timeline", "unread", "offered_at", "accepted_at",
            "picked_up_at", "delivered_at", "created_at",
        ]

    def get_pickup(self, delivery):
        return pickup_of(delivery.vendor_order.vendor)

    def get_dropoff(self, delivery):
        order = delivery.vendor_order.order
        # Coordonnées du client visibles seulement une fois la course acceptée
        hide = delivery.status in (Delivery.Status.OPEN, Delivery.Status.OFFERED)
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

    def get_cash_for_shop(self, delivery):
        from .services import cash_for_shop

        return cash_for_shop(delivery)

    def get_items(self, delivery):
        return [{"name": i.product_name, "quantity": i.quantity} for i in delivery.vendor_order.items.all()]

    def get_items_count(self, delivery):
        return sum(i.quantity for i in delivery.vendor_order.items.all())

    def get_offer_expires_at(self, delivery):
        from .services import offer_expires_at

        return offer_expires_at(delivery)

    def get_timeline(self, delivery):
        from apps.orders.timeline import vendor_order_timeline

        return vendor_order_timeline(delivery.vendor_order)

    def get_unread(self, delivery):
        return unread_for(delivery, self.context["request"].user)


class CandidateRiderSerializer(serializers.ModelSerializer):
    """Livreur proposé à la boutique pour une course."""

    name = serializers.CharField(source="user.name")
    phone = serializers.CharField(source="user.phone")
    avatar = serializers.SerializerMethodField()
    rating = serializers.SerializerMethodField()
    done = serializers.IntegerField()
    done_for_shop = serializers.IntegerField()
    busy = serializers.IntegerField()
    declined = serializers.BooleanField()
    cash_blocked = serializers.BooleanField()

    class Meta:
        model = Rider
        fields = ["id", "name", "phone", "avatar", "vehicle_plate", "communes", "rating", "done", "done_for_shop",
                  "busy", "declined", "cash_blocked"]

    def get_avatar(self, rider):
        return avatar_url(rider.user)

    def get_rating(self, rider):
        return round(rider.avg_rating, 1) if rider.avg_rating else None


def delivery_for_vendor(delivery, user):
    """Course vue par la boutique : le livreur choisi, où il en est, la discussion."""
    from .services import cash_for_shop, offer_expires_at, rider_rating

    if delivery is None:
        return None
    rider = delivery.rider
    return {
        "cash_for_shop": cash_for_shop(delivery),
        "cash_handed_at": delivery.cash_handed_at,
        "id": delivery.id,
        "status": delivery.status,
        "status_label": delivery.get_status_display(),
        "fee": delivery.fee,
        "offer_expires_at": offer_expires_at(delivery),
        "unread": unread_for(delivery, user),
        "rider": rider and {
            "id": rider.id,
            "name": rider.user.name,
            "phone": rider.user.phone,
            "avatar": avatar_url(rider.user),
            "vehicle_plate": rider.vehicle_plate,
            "rating": rider_rating(rider)["rating"],
        },
    }


class DeliveryMessageSerializer(serializers.ModelSerializer):
    sender_name = serializers.CharField(source="sender.name", read_only=True)
    mine = serializers.SerializerMethodField()

    class Meta:
        model = DeliveryMessage
        fields = ["id", "sender", "sender_name", "mine", "content", "image", "audio", "read_at", "created_at"]
        read_only_fields = ["sender", "read_at"]

    def get_mine(self, message):
        return message.sender_id == self.context["request"].user.id

    def validate(self, attrs):
        if not attrs.get("content", "").strip() and not attrs.get("image") and not attrs.get("audio"):
            raise serializers.ValidationError("Message vide.")
        if attrs.get("audio"):
            try:
                check_upload(attrs["audio"], {"audio"}, max_mb=15)
            except DjangoValidationError as e:
                raise serializers.ValidationError({"audio": e.messages[0]})
        if attrs.get("image"):
            try:
                check_upload(attrs["image"], {"image"}, max_mb=15)
            except DjangoValidationError as e:
                raise serializers.ValidationError({"image": e.messages[0]})
        return attrs
