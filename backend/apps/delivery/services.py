"""
Courses de livraison.

1. Le colis est prêt : la course est créée, « livreur à choisir ».
2. La boutique choisit un livreur parmi ceux qui desservent sa zone : la course lui est proposée.
3. Le livreur accepte (il voit alors l'adresse du client) ou refuse ; sans réponse sous
   DELIVERY_OFFER_MINUTES, la proposition expire. Dans les deux cas la boutique en choisit un autre.
4. Il récupère le colis à l'adresse de retrait de la boutique, puis le remet contre le code du client.

Aucun livreur ne voit une course qui ne lui a pas été proposée par la boutique.
"""

from datetime import timedelta

from django.conf import settings
from django.db import transaction
from django.db.models import Avg, Count, Q
from django.utils import timezone

from apps.core.formatting import gnf
from apps.notifications.services import notify, push_to_user
from apps.orders.models import Order, VendorOrder
from apps.orders.services import OrderError, check_delivery_code, update_vendor_order_status
from apps.wallet.models import LedgerEntry

from .models import Delivery, DeliveryRating, Rider

ACTIVE_STATUSES = [Delivery.Status.OFFERED, Delivery.Status.ASSIGNED, Delivery.Status.PICKED_UP]


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


def offer_expires_at(delivery):
    if delivery.status != Delivery.Status.OFFERED or not delivery.offered_at:
        return None
    return delivery.offered_at + timedelta(minutes=settings.DELIVERY_OFFER_MINUTES)


def vendor_link(delivery):
    return f"/vendeur/commandes/{delivery.vendor_order_id}"


def rider_link(delivery):
    return f"/livreur/courses/{delivery.id}"


def notify_change(delivery):
    """Met à jour en direct les écrans de la boutique et du livreur."""
    event = {"type": "delivery.updated", "delivery": delivery.id, "vendor_order": delivery.vendor_order_id}
    users = [delivery.vendor_order.vendor.user_id] + ([delivery.rider.user_id] if delivery.rider_id else [])
    transaction.on_commit(lambda: [push_to_user(u, event) for u in users])


# ---------------------------------------------------------------- Choix du livreur par la boutique

def candidate_riders(delivery):
    """
    Livreurs que la boutique peut choisir : validés, disponibles, de la ville de retrait,
    qui desservent la commune de la boutique ou celle du client (ou toute la ville).
    Ceux qui ont déjà livré pour la boutique passent en premier, puis les mieux notés.
    """
    vendor = delivery.vendor_order.vendor
    order = delivery.vendor_order.order
    city = vendor.pickup_city or order.delivery_city
    places = {c.lower() for c in (vendor.pickup_commune, order.delivery_commune) if c}
    riders = (
        Rider.objects.filter(is_approved=True, is_available=True, user__is_active=True, city__iexact=city)
        .select_related("user")
        .annotate(
            done=Count("deliveries", filter=Q(deliveries__status=Delivery.Status.DELIVERED), distinct=True),
            done_for_shop=Count(
                "deliveries",
                filter=Q(deliveries__status=Delivery.Status.DELIVERED, deliveries__vendor_order__vendor=vendor),
                distinct=True,
            ),
            busy=Count("deliveries", filter=Q(deliveries__status__in=ACTIVE_STATUSES), distinct=True),
            avg_rating=Avg("deliveries__rating__rating"),
        )
        .order_by("-done_for_shop", "-avg_rating", "busy", "-done")
    )
    declined = set(delivery.declined_by.values_list("pk", flat=True))
    cash = cash_to_collect(delivery)
    result = []
    for rider in riders:
        communes = {c.lower() for c in rider.communes_list}
        if communes and places and not communes & places:
            continue
        rider.declined = rider.pk in declined
        rider.cash_blocked = not can_carry_cash(rider, cash)
        result.append(rider)
    return result


@transaction.atomic
def offer(delivery, rider, by_user):
    """La boutique propose la course à un livreur. Une proposition sans réponse peut être remplacée."""
    delivery = Delivery.objects.select_for_update(of=("self",)).select_related("vendor_order__vendor", "vendor_order__order", "rider__user").get(pk=delivery.pk)
    if delivery.status in (Delivery.Status.ASSIGNED, Delivery.Status.PICKED_UP):
        raise OrderError(f"{delivery.rider.user.name} a déjà accepté cette course. Retirez-la-lui d'abord.")
    if delivery.status not in (Delivery.Status.OPEN, Delivery.Status.OFFERED):
        raise OrderError("Cette course est terminée.")
    if not delivery.vendor_order.vendor.has_pickup_address:
        raise OrderError("Renseignez d'abord l'adresse de retrait de votre boutique : le livreur doit savoir où venir.")
    candidates = {r.pk: r for r in candidate_riders(delivery)}
    chosen = candidates.get(rider.pk)
    if chosen is None:
        raise OrderError("Ce livreur n'est pas disponible dans votre zone.")
    if chosen.cash_blocked:
        raise OrderError(f"{rider.user.name} doit d'abord reverser les espèces qu'il a encaissées. Choisissez un autre livreur.")

    previous = delivery.rider if delivery.status == Delivery.Status.OFFERED and delivery.rider_id != rider.pk else None
    delivery.rider = rider
    delivery.status = Delivery.Status.OFFERED
    delivery.offered_at = timezone.now()
    delivery.save(update_fields=["rider", "status", "offered_at", "updated_at"])
    delivery.declined_by.remove(rider)

    vendor = delivery.vendor_order.vendor
    order = delivery.vendor_order.order
    if previous:
        notify(previous.user, "delivery", f"Proposition retirée – commande #{order.id}",
               f"{vendor.shop_name} a confié la course à un autre livreur.", "/livreur", whatsapp=False)
    cash = cash_to_collect(delivery)
    notify(
        rider.user, "delivery", f"{vendor.shop_name} vous propose une course 🛵",
        f"Retrait : {vendor.pickup_quartier}, {vendor.pickup_commune}. Livraison : {order.delivery_commune or order.delivery_city}. "
        f"Gain : {gnf(delivery.fee)}" + (f", espèces à encaisser : {gnf(cash)}" if cash else "")
        + f". Répondez sous {settings.DELIVERY_OFFER_MINUTES} min.",
        rider_link(delivery),
    )
    notify_change(delivery)
    return delivery


@transaction.atomic
def withdraw(delivery, by_user):
    """La boutique reprend la course (le livreur n'a pas encore le colis)."""
    delivery = Delivery.objects.select_for_update(of=("self",)).select_related("rider__user", "vendor_order").get(pk=delivery.pk)
    if delivery.status not in (Delivery.Status.OFFERED, Delivery.Status.ASSIGNED):
        raise OrderError("Le livreur a déjà le colis : la course ne peut plus lui être retirée.")
    rider = delivery.rider
    notify_change(delivery)
    _reopen(delivery)
    notify(rider.user, "delivery", f"Course retirée – commande #{delivery.vendor_order.order_id}",
           "La boutique a repris cette course. Inutile de vous déplacer.", "/livreur")
    return delivery


def _reopen(delivery):
    delivery.rider = None
    delivery.status = Delivery.Status.OPEN
    delivery.offered_at = None
    delivery.accepted_at = None
    delivery.save(update_fields=["rider", "status", "offered_at", "accepted_at", "updated_at"])


# ---------------------------------------------------------------- Réponses du livreur

def _offer_for(delivery, rider):
    delivery = Delivery.objects.select_for_update(of=("self",)).select_related(
        "vendor_order__order", "vendor_order__vendor__user"
    ).get(pk=delivery.pk)
    if delivery.status != Delivery.Status.OFFERED or delivery.rider_id != rider.pk:
        raise OrderError("Cette course ne vous est plus proposée.")
    return delivery


@transaction.atomic
def accept(delivery, rider):
    delivery = _offer_for(delivery, rider)
    if offer_expires_at(delivery) < timezone.now():
        raise OrderError("La proposition a expiré. La boutique peut vous la proposer à nouveau.")
    if not can_carry_cash(rider, cash_to_collect(delivery)):
        raise OrderError(
            f"Vous devez d'abord reverser {gnf(cash_debt(rider.user))} d'espèces encaissées "
            "avant de prendre une course payée à la livraison."
        )
    delivery.status = Delivery.Status.ASSIGNED
    delivery.accepted_at = timezone.now()
    delivery.save(update_fields=["status", "accepted_at", "updated_at"])
    vo = delivery.vendor_order
    notify(vo.vendor.user, "delivery", f"{rider.user.name} arrive – commande #{vo.order_id}",
           f"Il a accepté la course et vient chercher le colis. Tél : {rider.user.phone}.", vendor_link(delivery))
    notify_change(delivery)
    return delivery


@transaction.atomic
def decline(delivery, rider):
    delivery = _offer_for(delivery, rider)
    _reopen(delivery)
    delivery.declined_by.add(rider)
    vo = delivery.vendor_order
    notify(vo.vendor.user, "delivery", f"{rider.user.name} a refusé la course – commande #{vo.order_id}",
           "Choisissez un autre livreur.", vendor_link(delivery))
    transaction.on_commit(lambda: push_to_user(vo.vendor.user_id, {"type": "delivery.updated", "delivery": delivery.id}))
    return delivery


@transaction.atomic
def release(delivery, rider):
    """Le livreur rend une course acceptée qu'il n'a pas encore récupérée."""
    delivery = Delivery.objects.select_for_update(of=("self",)).select_related("vendor_order__vendor__user").get(pk=delivery.pk, rider=rider)
    if delivery.status != Delivery.Status.ASSIGNED:
        raise OrderError("Vous avez déjà récupéré le colis : livrez-le ou contactez le service client.")
    _reopen(delivery)
    delivery.declined_by.add(rider)
    vo = delivery.vendor_order
    notify(vo.vendor.user, "delivery", f"{rider.user.name} ne peut plus livrer – commande #{vo.order_id}",
           "Choisissez un autre livreur.", vendor_link(delivery))
    transaction.on_commit(lambda: push_to_user(vo.vendor.user_id, {"type": "delivery.updated", "delivery": delivery.id}))
    return delivery


def expire_offers():
    """Propositions restées sans réponse : la boutique est prévenue pour choisir quelqu'un d'autre."""
    limit = timezone.now() - timedelta(minutes=settings.DELIVERY_OFFER_MINUTES)
    count = 0
    for pk in Delivery.objects.filter(status=Delivery.Status.OFFERED, offered_at__lt=limit).values_list("pk", flat=True):
        with transaction.atomic():
            delivery = Delivery.objects.select_for_update(of=("self",)).select_related("rider__user", "vendor_order__vendor__user").get(pk=pk)
            if delivery.status != Delivery.Status.OFFERED:
                continue
            rider = delivery.rider
            _reopen(delivery)
            vo = delivery.vendor_order
            notify(vo.vendor.user, "delivery", f"Pas de réponse de {rider.user.name} – commande #{vo.order_id}",
                   "La proposition a expiré. Choisissez un autre livreur.", vendor_link(delivery))
            notify(rider.user, "delivery", "Proposition expirée",
                   f"La course de {vo.vendor.shop_name} ne vous est plus proposée.", "/livreur", whatsapp=False)
            count += 1
    return count


# ---------------------------------------------------------------- Retrait et remise du colis

@transaction.atomic
def picked_up(delivery, rider):
    delivery = Delivery.objects.select_for_update(of=("self",)).select_related("vendor_order__vendor__user").get(pk=delivery.pk, rider=rider)
    if delivery.status != Delivery.Status.ASSIGNED:
        raise OrderError("Course dans un état inattendu.")
    delivery.status = Delivery.Status.PICKED_UP
    delivery.picked_up_at = timezone.now()
    delivery.save(update_fields=["status", "picked_up_at", "updated_at"])
    vo = delivery.vendor_order
    order = vo.order
    notify(vo.vendor.user, "delivery", f"Colis remis à {rider.user.name} – commande #{order.id}",
           "Il est en route vers le client.", vendor_link(delivery), whatsapp=False)
    notify(order.user, "delivery", "Votre colis est en route 🛵",
           f"{rider.user.name} vous livre la commande #{order.id}. Tél : {rider.user.phone}. "
           "Préparez votre code de livraison.", f"/commandes/{order.id}")
    notify_change(delivery)
    return delivery


def delivered(delivery, rider, code):
    """Remise du colis : le code du client est vérifié d'abord (hors transaction, pour compter les essais)."""
    delivery = Delivery.objects.select_related("vendor_order").get(pk=delivery.pk, rider=rider)
    if delivery.status != Delivery.Status.PICKED_UP:
        raise OrderError("Récupérez d'abord le colis à la boutique.")
    check_delivery_code(delivery.vendor_order, code)
    with transaction.atomic():
        delivery = Delivery.objects.select_for_update(of=("self",)).select_related("vendor_order__vendor").get(pk=delivery.pk)
        delivery.status = Delivery.Status.DELIVERED
        delivery.delivered_at = timezone.now()
        delivery.save(update_fields=["status", "delivered_at", "updated_at"])
        if delivery.vendor_order.status == VendorOrder.Status.READY:
            update_vendor_order_status(delivery.vendor_order, VendorOrder.Status.DELIVERED, rider.user, code_checked=True)
        notify_change(delivery)
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


# ---------------------------------------------------------------- Discussion boutique ↔ livreur

def chat_partner(delivery, user):
    return delivery.rider.user if user.id == delivery.vendor_order.vendor.user_id else delivery.vendor_order.vendor.user


def send_message(delivery, sender, serializer):
    if delivery.status not in ACTIVE_STATUSES:
        raise OrderError("La course est terminée : la discussion est fermée.")
    message = serializer.save(delivery=delivery, rider=delivery.rider, sender=sender)
    partner = chat_partner(delivery, sender)
    event = {"type": "delivery.message", "delivery": delivery.id, "vendor_order": delivery.vendor_order_id}
    transaction.on_commit(lambda: [push_to_user(u, event) for u in (partner.id, sender.id)])
    # Notification au premier message non lu seulement (pas d'avalanche)
    unread = delivery.messages.filter(rider=delivery.rider, read_at__isnull=True, sender=sender).count()
    if unread == 1:
        from_shop = sender.id == delivery.vendor_order.vendor.user_id
        name = delivery.vendor_order.vendor.shop_name if from_shop else sender.name
        notify(partner, "message", f"Message de {name} – commande #{delivery.vendor_order.order_id}",
               message.content[:120] or ("🎤 Message vocal" if message.audio else "📷 Photo"), rider_link(delivery) if from_shop else vendor_link(delivery),
               whatsapp=False)
    return message


def mark_messages_read(delivery, reader):
    return delivery.messages.filter(rider=delivery.rider, read_at__isnull=True).exclude(sender=reader).update(read_at=timezone.now())
