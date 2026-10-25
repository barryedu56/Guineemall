"""
Livraison bloquée après trop de codes faux.

Règle : la parole du client prime. S'il a dit « rien reçu », seul lui peut revenir dessus (l'équipe lui
demande de confirmer après l'appel). S'il n'a rien répondu, l'équipe peut valider la réception après
l'avoir appelé, mais il peut la contester pendant DELIVERY_CONTEST_HOURS et la boutique n'est payée qu'après.

Deux explications possibles : le client s'est trompé (cas fréquent), ou quelqu'un essaie de déclarer
« livrée » une commande qu'il n'a pas remise (pour une commande payée en ligne, cela lancerait le
versement à la boutique). Le client est le seul à savoir s'il a le colis : il est prévenu, peut répondre
lui-même, et l'équipe tranche après l'avoir appelé.
"""

from datetime import timedelta

from django.conf import settings
from django.db import transaction
from django.utils import timezone

from apps.core.audit import audit
from apps.core.formatting import gnf
from apps.notifications.services import notify, notify_team

from .models import Order, OrderStatusHistory, VendorOrder
from .services import ONLINE_METHODS, OrderError, _refund, _restore_stock, _sync_order_status, update_vendor_order_status

SUSPICIOUS_BLOCKS = 2  # blocages sur 30 jours au-delà desquels une boutique est signalée


def _client_link(vendor_order):
    return f"/commandes/{vendor_order.order_id}"


def warn_client(vendor_order):
    order = vendor_order.order
    notify(order.user, "security", f"Mauvais code saisi pour votre commande #{order.id}",
           f"Quelqu'un a saisi un code faux pour le colis de {vendor_order.vendor.shop_name}. Si vous avez le colis "
           "entre les mains, donnez le code affiché dans votre suivi. Sinon, ne donnez votre code à personne et "
           "signalez-le depuis votre commande.", _client_link(vendor_order))


def code_blocked(vendor_order):
    """5ᵉ code faux : client, boutique et équipe prévenus ; boutique signalée si cela se répète."""
    order = vendor_order.order
    vendor = vendor_order.vendor
    notify(order.user, "security", f"Livraison de la commande #{order.id} bloquée par sécurité",
           f"Trop de codes faux ont été saisis pour le colis de {vendor.shop_name}. Notre équipe va vous appeler. "
           "Dites-nous dès maintenant depuis votre commande si vous avez reçu le colis ou non.", _client_link(vendor_order))
    notify(vendor.user, "order", f"Livraison bloquée – commande #{order.id}",
           "Trop de codes faux. L'équipe GuinéeMall contacte le client et vous tient informé.",
           f"/vendeur/commandes/{vendor_order.id}")
    notify_team(f"Code de livraison bloqué – commande #{order.id}",
                f"{settings.DELIVERY_CODE_MAX_ATTEMPTS} codes faux ({vendor.shop_name}). Appelez le client avant de trancher.",
                f"/administration/commandes/{order.id}")
    since = timezone.now() - timedelta(days=30)
    blocks = VendorOrder.objects.filter(
        vendor=vendor, delivery_code_attempts__gte=settings.DELIVERY_CODE_MAX_ATTEMPTS, updated_at__gte=since
    ).count()
    if blocks >= SUSPICIOUS_BLOCKS:
        notify_team(f"Comportement suspect : {vendor.shop_name}",
                    f"{blocks} livraisons bloquées pour codes faux en 30 jours. Vérifiez la boutique et envisagez une suspension.",
                    "/administration/boutiques?status=approved")


def is_blocked(vendor_order):
    return (vendor_order.status == VendorOrder.Status.READY
            and vendor_order.delivery_code_attempts >= settings.DELIVERY_CODE_MAX_ATTEMPTS)


def is_contested(vendor_order):
    """Livrée (validée par l'équipe), mais le client dit ne rien avoir reçu : argent gelé, à trancher."""
    return (vendor_order.status == VendorOrder.Status.DELIVERED
            and vendor_order.client_report == VendorOrder.ClientReport.NOT_RECEIVED
            and vendor_order.funds_released_at is None)


def can_contest(vendor_order):
    return (vendor_order.status == VendorOrder.Status.DELIVERED and vendor_order.contest_until is not None
            and vendor_order.contest_until > timezone.now() and not vendor_order.client_report)


@transaction.atomic
def mark_delivered_without_code(vendor_order, by, note, by_client=True):
    """
    Le colis est arrivé. by_client : c'est le client qui le dit (la boutique peut être payée) ;
    sinon c'est l'équipe, et le client garde DELIVERY_CONTEST_HOURS pour contester.
    """
    from apps.delivery.models import Delivery

    vendor_order = VendorOrder.objects.select_for_update().select_related("order", "vendor__user").get(pk=vendor_order.pk)
    if vendor_order.status != VendorOrder.Status.READY:
        raise OrderError("Cette livraison n'est plus en attente.")
    for delivery in Delivery.objects.filter(vendor_order=vendor_order, status=Delivery.Status.PICKED_UP):
        delivery.status = Delivery.Status.DELIVERED
        delivery.delivered_at = timezone.now()
        delivery.save(update_fields=["status", "delivered_at", "updated_at"])
    update_vendor_order_status(vendor_order, VendorOrder.Status.DELIVERED, by, code_checked=True)
    vendor_order.refresh_from_db()
    if not by_client:
        vendor_order.contest_until = timezone.now() + timedelta(hours=settings.DELIVERY_CONTEST_HOURS)
        vendor_order.save(update_fields=["contest_until", "updated_at"])
    elif vendor_order.order.payment_method in ONLINE_METHODS and not vendor_order.received_at:
        from apps.wallet.services import release_funds

        # Le client a confirmé avoir le colis : la boutique peut être payée
        vendor_order.received_at = timezone.now()
        vendor_order.save(update_fields=["received_at", "updated_at"])
        transaction.on_commit(lambda: release_funds(vendor_order))
    OrderStatusHistory.objects.create(order=vendor_order.order, status=vendor_order.order.status, changed_by=by, note=note)
    return vendor_order


def client_report(vendor_order, user, received):
    """Réponse du client : livraison bloquée, confirmation demandée par l'équipe, ou contestation."""
    if vendor_order.order.user_id != user.id:
        raise OrderError("Commande introuvable.")
    order = vendor_order.order
    if received:
        if is_blocked(vendor_order):
            _set_report(vendor_order, VendorOrder.ClientReport.RECEIVED)
            mark_delivered_without_code(vendor_order, user, f"{vendor_order.vendor} : le client confirme avoir reçu le colis")
        elif is_contested(vendor_order) or can_contest(vendor_order):
            _set_report(vendor_order, VendorOrder.ClientReport.RECEIVED)
            _client_confirms_delivered(vendor_order)
        else:
            raise OrderError("Rien à confirmer pour ce colis.")
        audit(user, "delivery_code.client_received", vendor_order)
        notify(vendor_order.vendor.user, "order", f"Commande #{order.id} : le client confirme la réception ✅",
               "La livraison est validée.", f"/vendeur/commandes/{vendor_order.id}")
        return
    if not (is_blocked(vendor_order) or can_contest(vendor_order)):
        raise OrderError("Ce colis ne peut plus être signalé. Contactez le service client.")
    _set_report(vendor_order, VendorOrder.ClientReport.NOT_RECEIVED)
    audit(user, "delivery_code.client_not_received", vendor_order)
    notify_team(f"🚨 Commande #{order.id} : le client dit ne rien avoir reçu",
                f"{vendor_order.vendor.shop_name} : paiement de la boutique gelé. Appelez le client et la boutique.",
                f"/administration/commandes/{order.id}")
    notify(order.user, "order", "Signalement bien reçu",
           "Notre équipe vous appelle rapidement. La boutique ne sera pas payée tant que ce n'est pas réglé.",
           _client_link(vendor_order), whatsapp=False)


def _set_report(vendor_order, report):
    VendorOrder.objects.filter(pk=vendor_order.pk).update(client_report=report, client_report_at=timezone.now())
    vendor_order.client_report = report


@transaction.atomic
def _client_confirms_delivered(vendor_order):
    """Livraison validée par l'équipe, confirmée ensuite par le client : la boutique peut être payée."""
    from apps.wallet.services import release_funds

    vendor_order = VendorOrder.objects.select_for_update().select_related("order").get(pk=vendor_order.pk)
    vendor_order.contest_until = None
    fields = ["contest_until", "updated_at"]
    if vendor_order.order.payment_method in ONLINE_METHODS and not vendor_order.received_at:
        vendor_order.received_at = timezone.now()
        fields.append("received_at")
    vendor_order.save(update_fields=fields)
    transaction.on_commit(lambda: release_funds(vendor_order))


DECISIONS = {
    "received": "Réception validée par l'équipe",
    "ask_client": "Confirmation demandée au client",
    "retry": "Nouveaux essais de code",
    "cancel": "Livraison annulée",
}


def admin_decision(admin, vendor_order, decision, note):
    """L'équipe tranche une livraison bloquée, après avoir appelé le client."""
    if decision not in DECISIONS:
        raise OrderError("Décision inconnue.")
    if not note.strip():
        raise OrderError("Indiquez le motif (ce que le client a dit au téléphone).")
    blocked, contested = is_blocked(vendor_order), is_contested(vendor_order)
    if not (blocked or contested):
        raise OrderError("Cette livraison n'est ni bloquée ni contestée.")
    said_not_received = vendor_order.client_report == VendorOrder.ClientReport.NOT_RECEIVED
    if decision == "received" and said_not_received:
        raise OrderError("Le client a dit ne rien avoir reçu : seul lui peut revenir dessus. Demandez-lui de confirmer.")
    if decision == "retry" and not blocked:
        raise OrderError("Le colis est déjà marqué livré.")
    order = vendor_order.order
    shop = vendor_order.vendor
    audit(admin, f"delivery_code.{decision}", vendor_order, {"note": note, "attempts": vendor_order.delivery_code_attempts})
    if decision == "ask_client":
        VendorOrder.objects.filter(pk=vendor_order.pk).update(confirmation_requested_at=timezone.now())
        notify(order.user, "order", f"Commande #{order.id} : avez-vous reçu votre colis ?",
               f"Suite à notre échange ({note}), confirmez dans votre commande si vous avez bien reçu le colis de "
               f"{shop.shop_name}. Tant que vous ne confirmez pas, la boutique n'est pas payée.", _client_link(vendor_order))
        return
    if decision == "received":
        mark_delivered_without_code(vendor_order, admin, f"{shop} : réception validée par l'équipe ({note})", by_client=False)
        hours = settings.DELIVERY_CONTEST_HOURS
        client_msg = (f"Notre équipe a validé la réception de votre colis ({note}). Ce n'est pas le cas ? "
                      f"Signalez-le depuis votre commande dans les {hours} h.")
        shop_msg = f"Livraison validée par l'équipe. Le client peut la contester pendant {hours} h."
    elif decision == "retry":
        VendorOrder.objects.filter(pk=vendor_order.pk).update(delivery_code_attempts=0, client_report="", client_report_at=None)
        client_msg = "Vous pouvez donner à nouveau votre code de livraison (affiché dans votre suivi) à la remise du colis."
        shop_msg = "La livraison est débloquée : demandez au client le code affiché dans son suivi."
    else:
        _cancel_blocked(vendor_order, admin, note)
        refund = order.payment_method in ONLINE_METHODS
        client_msg = f"Livraison annulée par notre équipe ({note})." + (f" {gnf(vendor_order.total_amount)} vous sont remboursés en avoir." if refund else "")
        shop_msg = f"Livraison annulée par l'équipe GuinéeMall : {note}."
    notify(order.user, "order", f"Commande #{order.id} : {DECISIONS[decision].lower()}", client_msg, _client_link(vendor_order))
    notify(shop.user, "order", f"Commande #{order.id} : {DECISIONS[decision].lower()}", shop_msg, f"/vendeur/commandes/{vendor_order.id}")


@transaction.atomic
def _cancel_blocked(vendor_order, admin, note):
    from apps.delivery.models import Delivery

    vendor_order = VendorOrder.objects.select_for_update().select_related("order", "vendor").get(pk=vendor_order.pk)
    order = Order.objects.select_for_update().get(pk=vendor_order.order_id)
    vendor_order.status = VendorOrder.Status.CANCELLED
    vendor_order.save(update_fields=["status", "updated_at"])
    Delivery.objects.filter(vendor_order=vendor_order).exclude(status=Delivery.Status.DELIVERED).update(status=Delivery.Status.CANCELLED)
    _restore_stock(vendor_order)
    _refund(order, [vendor_order], "livraison annulée par l'équipe")
    OrderStatusHistory.objects.create(order=order, status=VendorOrder.Status.CANCELLED, changed_by=admin,
                                      note=f"{vendor_order.vendor} : livraison annulée par l'équipe ({note})")
    _sync_order_status(order, admin)
