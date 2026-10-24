"""
Portefeuilles et séquestre.

Quand une sous-commande est terminée, on répartit l'argent :
- la boutique reçoit le prix des articles, moins sa propre réduction et la commission GuinéeMall ;
- les frais de livraison vont au livreur GuinéeMall s'il y en a eu un, sinon à la boutique ;
- l'ambassadeur qui a amené le client touche sa commission (prise sur la marge de la plateforme).

Paiement à la livraison : celui qui livre a encaissé l'argent en espèces, son portefeuille
est donc débité de ce montant (un solde négatif est une dette envers GuinéeMall).

Paiement en ligne : l'argent reste bloqué (séquestre) jusqu'à ce que le client confirme
la réception, ou automatiquement ESCROW_AUTO_RELEASE_DAYS jours après la livraison.
"""

from django.conf import settings
from django.db import transaction
from django.utils import timezone

from apps.core.formatting import gnf
from apps.notifications.services import notify

from .models import LedgerEntry, PayoutRequest


def commission_for(vendor_order):
    base = vendor_order.subtotal - vendor_order.vendor_discount
    return round(base * vendor_order.vendor.effective_commission_rate)


def _credit(user, kind, amount, vendor_order, note):
    if amount == 0:
        return None
    entry, _ = LedgerEntry.objects.get_or_create(
        user=user, vendor_order=vendor_order, kind=kind, defaults={"amount": amount, "note": note}
    )
    return entry


@transaction.atomic
def release_funds(vendor_order):
    """Répartit l'argent d'une sous-commande livrée. Sans effet si c'est déjà fait."""
    from apps.orders.models import Order, VendorOrder

    vendor_order = VendorOrder.objects.select_for_update().select_related("order", "vendor__user").get(pk=vendor_order.pk)
    if vendor_order.funds_released_at or vendor_order.status != VendorOrder.Status.DELIVERED:
        return False

    order = vendor_order.order
    cash = order.payment_method == Order.PaymentMethod.CASH_ON_DELIVERY
    delivery = getattr(vendor_order, "delivery", None)
    rider = delivery.rider if delivery and delivery.status == "delivered" and delivery.rider else None

    commission = commission_for(vendor_order)
    goods = vendor_order.subtotal - vendor_order.vendor_discount - commission
    vendor_amount = goods + (0 if rider else vendor_order.delivery_fee)
    if cash and rider is None:
        vendor_amount -= vendor_order.total_amount  # la boutique a encaissé le client
    _credit(
        vendor_order.vendor.user, LedgerEntry.Kind.SALE if not cash or rider else LedgerEntry.Kind.COD_COMMISSION,
        vendor_amount, vendor_order, f"Commande #{order.id} (commission {gnf(commission)})",
    )

    if rider:
        rider_amount = vendor_order.delivery_fee - (vendor_order.total_amount if cash else 0)
        _credit(rider.user, LedgerEntry.Kind.DELIVERY, rider_amount, vendor_order, f"Livraison commande #{order.id}")

    if order.affiliate_id:
        affiliate_amount = round((vendor_order.subtotal - vendor_order.vendor_discount) * settings.AFFILIATE_COMMISSION_RATE)
        _credit(order.affiliate, LedgerEntry.Kind.AFFILIATE, affiliate_amount, vendor_order, f"Vente commande #{order.id}")
        notify(order.affiliate, "wallet", "Commission ambassadeur 🎉",
               f"Vous avez gagné {gnf(affiliate_amount)} grâce à votre lien.", "/portefeuille", whatsapp=False)

    vendor_order.funds_released_at = timezone.now()
    vendor_order.save(update_fields=["funds_released_at", "updated_at"])
    if not cash:
        notify(vendor_order.vendor.user, "wallet", "Paiement disponible",
               f"{gnf(vendor_amount)} de la commande #{order.id} sont disponibles dans votre portefeuille.",
               "/vendeur/portefeuille")
    return True


@transaction.atomic
def request_payout(user, amount, method, phone):
    balance = LedgerEntry.balance(user)
    pending = sum(user.payouts.filter(status=PayoutRequest.Status.PENDING).values_list("amount", flat=True))
    if amount <= 0:
        raise ValueError("Le montant doit être positif.")
    if amount > balance - pending:
        raise ValueError(f"Solde disponible insuffisant ({gnf(max(0, balance - pending))}).")
    return PayoutRequest.objects.create(user=user, amount=amount, method=method, phone=phone)


@transaction.atomic
def process_payout(payout, admin, paid, reference="", note=""):
    payout = PayoutRequest.objects.select_for_update().get(pk=payout.pk)
    if payout.status != PayoutRequest.Status.PENDING:
        return payout
    payout.status = PayoutRequest.Status.PAID if paid else PayoutRequest.Status.REJECTED
    payout.transaction_reference = reference
    payout.admin_note = note
    payout.processed_by = admin
    payout.processed_at = timezone.now()
    payout.save()
    if paid:
        LedgerEntry.objects.create(
            user=payout.user, kind=LedgerEntry.Kind.PAYOUT, amount=-payout.amount, payout=payout,
            note=f"Retrait {payout.get_method_display()} {payout.phone}",
        )
        notify(payout.user, "wallet", "Retrait effectué",
               f"{gnf(payout.amount)} ont été envoyés sur votre compte {payout.get_method_display()}.", "/portefeuille")
    else:
        notify(payout.user, "wallet", "Retrait refusé", note or "Contactez GuinéeMall pour plus d'informations.",
               "/portefeuille")
    return payout
