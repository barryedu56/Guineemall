from django.db import transaction
from django.utils import timezone

from apps.notifications.services import notify, push_to_user
from apps.orders.models import VendorOrder
from apps.orders.services import OrderError, update_vendor_order_status

from .models import Delivery


@transaction.atomic
def accept(delivery, rider):
    delivery = Delivery.objects.select_for_update().get(pk=delivery.pk)
    if delivery.status != Delivery.Status.OPEN:
        raise OrderError("Cette course a déjà été prise par un autre livreur.")
    delivery.rider = rider
    delivery.status = Delivery.Status.ASSIGNED
    delivery.save(update_fields=["rider", "status", "updated_at"])
    vo = delivery.vendor_order
    notify(vo.vendor.user, "delivery", f"Livreur en route – commande #{vo.order_id}",
           f"{rider.user.name} ({rider.user.phone}) vient récupérer le colis.", "/vendeur/commandes")
    return delivery


@transaction.atomic
def picked_up(delivery, rider):
    delivery = Delivery.objects.select_for_update().get(pk=delivery.pk, rider=rider)
    if delivery.status != Delivery.Status.ASSIGNED:
        raise OrderError("Course dans un état inattendu.")
    delivery.status = Delivery.Status.PICKED_UP
    delivery.picked_up_at = timezone.now()
    delivery.save(update_fields=["status", "picked_up_at", "updated_at"])
    order = delivery.vendor_order.order
    notify(order.user, "delivery", f"Votre colis est en route 🛵",
           f"{rider.user.name} vous livre la commande #{order.id}. Tél : {rider.user.phone}", f"/commandes/{order.id}")
    return delivery


@transaction.atomic
def delivered(delivery, rider):
    delivery = Delivery.objects.select_for_update().get(pk=delivery.pk, rider=rider)
    if delivery.status != Delivery.Status.PICKED_UP:
        raise OrderError("Récupérez d'abord le colis à la boutique.")
    delivery.status = Delivery.Status.DELIVERED
    delivery.delivered_at = timezone.now()
    delivery.save(update_fields=["status", "delivered_at", "updated_at"])
    if delivery.vendor_order.status == VendorOrder.Status.READY:
        update_vendor_order_status(delivery.vendor_order, VendorOrder.Status.DELIVERED, rider.user)
    return delivery


def update_location(rider, latitude, longitude):
    rider.latitude = latitude
    rider.longitude = longitude
    rider.location_updated_at = timezone.now()
    rider.save(update_fields=["latitude", "longitude", "location_updated_at", "updated_at"])
    # Position envoyée en direct aux clients dont le colis est en route
    for delivery in rider.deliveries.filter(status=Delivery.Status.PICKED_UP).select_related("vendor_order__order"):
        push_to_user(delivery.vendor_order.order.user_id, {
            "type": "rider_location",
            "order_id": delivery.vendor_order.order_id,
            "latitude": str(latitude),
            "longitude": str(longitude),
        })
