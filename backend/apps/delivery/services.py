from django.conf import settings
from django.db import transaction
from django.db.models import Avg, Count
from django.utils import timezone

from apps.core.formatting import gnf
from apps.notifications.services import notify, push_to_user
from apps.orders.models import Order, VendorOrder
from apps.orders.services import OrderError, check_delivery_code, update_vendor_order_status
from apps.wallet.models import LedgerEntry

from .models import Delivery, DeliveryRating, Rider


def cash_to_collect(delivery):
    """Espèces que le livreur encaisse chez le client (0 si la commande est payée en ligne)."""
    vo = delivery.vendor_order
    return vo.total_amount if vo.order.payment_method == Order.PaymentMethod.CASH_ON_DELIVERY else 0


def cash_debt(user):
    """Ce que l'utilisateur doit à la plateforme (solde négatif du portefeuille)."""
    return max(0, -LedgerEntry.balance(user))


def can_carry_cash(rider, amount):
    """Plafond d'espèces : un livreur ne peut pas accumuler l'argent des clients sans le reverser."""
    return amount == 0 or cash_debt(rider.user) + amount <= settings.RIDER_CASH_LIMIT


def riders_for(delivery):
    """Livreurs validés, disponibles, de la ville (et des communes, s'ils en ont choisi) de la course."""
    order = delivery.vendor_order.order
    commune = order.delivery_commune.strip().lower()
    riders = Rider.objects.filter(is_approved=True, is_available=True, city__iexact=order.delivery_city).select_related("user")
    for rider in riders:
        communes = [c.strip().lower() for c in rider.communes.split(",") if c.strip()]
        if not communes or not commune or commune in communes:
            yield rider


def announce_to_riders(delivery):
    order = delivery.vendor_order.order
    place = order.delivery_commune or order.delivery_city
    for rider in riders_for(delivery):
        notify(rider.user, "delivery", f"Nouvelle course à {place} 🛵",
               f"Gain : {gnf(delivery.fee)}. Premier arrivé, premier servi.", "/livreur")


@transaction.atomic
def accept(delivery, rider):
    delivery = Delivery.objects.select_for_update().select_related("vendor_order__order").get(pk=delivery.pk)
    if delivery.status != Delivery.Status.OPEN:
        raise OrderError("Cette course a déjà été prise par un autre livreur.")
    if not can_carry_cash(rider, cash_to_collect(delivery)):
        raise OrderError(
            f"Vous devez d'abord reverser {gnf(cash_debt(rider.user))} d'espèces encaissées "
            "avant de prendre une course payée à la livraison."
        )
    delivery.rider = rider
    delivery.status = Delivery.Status.ASSIGNED
    delivery.save(update_fields=["rider", "status", "updated_at"])
    vo = delivery.vendor_order
    notify(vo.vendor.user, "delivery", f"Livreur en route – commande #{vo.order_id}",
           f"{rider.user.name} ({rider.user.phone}) vient récupérer le colis.", "/vendeur/commandes?statut=ready")
    return delivery


@transaction.atomic
def release(delivery, rider):
    """Le livreur rend une course qu'il n'a pas encore récupérée : elle redevient disponible."""
    delivery = Delivery.objects.select_for_update().get(pk=delivery.pk, rider=rider)
    if delivery.status != Delivery.Status.ASSIGNED:
        raise OrderError("Vous avez déjà récupéré le colis : livrez-le ou contactez le service client.")
    delivery.rider = None
    delivery.status = Delivery.Status.OPEN
    delivery.save(update_fields=["rider", "status", "updated_at"])
    vo = delivery.vendor_order
    notify(vo.vendor.user, "delivery", f"Livreur désisté – commande #{vo.order_id}",
           "La course est de nouveau proposée aux autres livreurs.", "/vendeur/commandes?statut=ready")
    transaction.on_commit(lambda: announce_to_riders(delivery))
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
    notify(order.user, "delivery", "Votre colis est en route 🛵",
           f"{rider.user.name} vous livre la commande #{order.id}. Tél : {rider.user.phone}. "
           "Préparez votre code de livraison.", f"/commandes/{order.id}")
    return delivery


def delivered(delivery, rider, code):
    """Remise du colis : le code du client est vérifié d'abord (hors transaction, pour compter les essais)."""
    delivery = Delivery.objects.select_related("vendor_order").get(pk=delivery.pk, rider=rider)
    if delivery.status != Delivery.Status.PICKED_UP:
        raise OrderError("Récupérez d'abord le colis à la boutique.")
    check_delivery_code(delivery.vendor_order, code)
    with transaction.atomic():
        delivery = Delivery.objects.select_for_update().get(pk=delivery.pk)
        delivery.status = Delivery.Status.DELIVERED
        delivery.delivered_at = timezone.now()
        delivery.save(update_fields=["status", "delivered_at", "updated_at"])
        if delivery.vendor_order.status == VendorOrder.Status.READY:
            update_vendor_order_status(delivery.vendor_order, VendorOrder.Status.DELIVERED, rider.user, code_checked=True)
    return delivery


def rate(delivery, user, rating, comment=""):
    if delivery.vendor_order.order.user_id != user.id:
        raise OrderError("Seul le client peut noter cette livraison.")
    if delivery.status != Delivery.Status.DELIVERED or delivery.rider is None:
        raise OrderError("La livraison n'est pas encore terminée.")
    if DeliveryRating.objects.filter(delivery=delivery).exists():
        raise OrderError("Vous avez déjà noté cette livraison.")
    return DeliveryRating.objects.create(delivery=delivery, rating=rating, comment=comment)


def rider_rating(rider):
    data = DeliveryRating.objects.filter(delivery__rider=rider).aggregate(avg=Avg("rating"), count=Count("id"))
    return {"rating": round(data["avg"], 1) if data["avg"] else None, "ratings_count": data["count"]}


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
