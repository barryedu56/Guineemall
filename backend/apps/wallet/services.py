"""
Portefeuilles et séquestre.

Quand une sous-commande est terminée, on répartit l'argent :
- la boutique reçoit le prix des articles, moins sa propre réduction et la commission GuinéeMall ;
- les frais de livraison vont au livreur GuinéeMall s'il y en a eu un, sinon à la boutique ;
- l'ambassadeur qui a amené le client touche sa commission (prise sur la marge de la plateforme).

Paiement à la livraison : GuinéeMall reste en dehors. Le livreur encaisse le client, garde le prix
de sa course et remet le reste à la boutique (ou la boutique encaisse elle-même). Pas de commission ;
seule une réduction offerte par GuinéeMall (code promo de la plateforme) est remboursée à la boutique.

Paiement en ligne : l'argent reste bloqué (séquestre) jusqu'à ce que le client confirme
la réception, ou automatiquement ESCROW_AUTO_RELEASE_DAYS jours après la livraison.
"""

from django.conf import settings
from django.db import transaction
from django.utils import timezone

from apps.core.formatting import gnf
from apps.core.audit import audit
from apps.notifications.services import notify, notify_team

from .models import CashDeposit, LedgerEntry, PayoutRequest


def commission_for(vendor_order):
    from apps.orders.models import Order

    if vendor_order.order.payment_method == Order.PaymentMethod.CASH_ON_DELIVERY:
        return 0  # l'argent ne passe pas par GuinéeMall
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
    # Le client conteste la livraison, ou peut encore la contester : on attend
    if vendor_order.client_report == VendorOrder.ClientReport.NOT_RECEIVED:
        return False
    if vendor_order.contest_until and vendor_order.contest_until > timezone.now() and not vendor_order.received_at:
        return False

    order = vendor_order.order
    if order.payment_method == Order.PaymentMethod.CASH_ON_DELIVERY:
        # Le client a payé en espèces la somme réduite : GuinéeMall rembourse sa propre réduction
        _credit(vendor_order.vendor.user, LedgerEntry.Kind.SALE, vendor_order.platform_discount, vendor_order,
                f"Commande #{order.id} : réduction offerte par GuinéeMall")
        vendor_order.funds_released_at = timezone.now()
        vendor_order.save(update_fields=["funds_released_at", "updated_at"])
        return True

    cash = False
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

    # Pas de commission d'ambassadeur à une boutique sur ses propres ventes
    if order.affiliate_id and order.affiliate_id != vendor_order.vendor.user_id:
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


IN_PROGRESS = [PayoutRequest.Status.PENDING, PayoutRequest.Status.SENDING]


@transaction.atomic
def request_payout(user, amount):
    """Retrait vers le numéro vérifié de l'utilisateur (jamais vers un numéro saisi au moment du retrait)."""
    from django.contrib.auth import get_user_model

    from .accounts import account_for_payout

    get_user_model().objects.select_for_update().get(pk=user.pk)  # deux demandes simultanées ne dépassent pas le solde
    account = account_for_payout(user)
    balance = LedgerEntry.balance(user)
    pending = sum(user.payouts.filter(status__in=IN_PROGRESS).values_list("amount", flat=True))
    if amount <= 0:
        raise ValueError("Le montant doit être positif.")
    if amount > balance - pending:
        raise ValueError(f"Solde disponible insuffisant ({gnf(max(0, balance - pending))}).")
    payout = PayoutRequest.objects.create(user=user, amount=amount, method=account.method, phone=account.phone)
    audit(user, "payout.requested", payout, {"amount": amount, "method": account.method})
    from .tasks import send_transfer

    # Envoi automatique si l'opérateur le permet ; sinon l'équipe est prévenue
    transaction.on_commit(lambda: send_transfer.delay("payout", payout.pk))
    notify_team("Demande de retrait", f"{user.name} : {gnf(amount)} vers {payout.get_method_display()} {payout.phone}.",
                "/administration/retraits?status=pending")
    return payout


@transaction.atomic
def process_payout(payout, admin, paid, reference="", note="", automatic=False):
    payout = PayoutRequest.objects.select_for_update().get(pk=payout.pk)
    if payout.status == PayoutRequest.Status.SENDING and not automatic:
        raise ValueError("Envoi automatique en cours : attendez la réponse de l'opérateur.")
    if payout.status not in IN_PROGRESS:
        return payout
    payout.status = PayoutRequest.Status.PAID if paid else PayoutRequest.Status.REJECTED
    payout.transaction_reference = reference
    payout.admin_note = note
    payout.processed_by = admin
    payout.processed_at = timezone.now()
    payout.save()
    audit(admin, "payout.paid" if paid else "payout.rejected", payout,
          {"amount": payout.amount, "reference": reference, "note": note})
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


def declare_deposit(user, amount, method, reference):
    """Le livreur (ou la boutique) déclare avoir reversé de l'argent ; un admin confirme ensuite."""
    debt = max(0, -LedgerEntry.balance(user))
    pending = sum(user.cash_deposits.filter(status=CashDeposit.Status.PENDING).values_list("amount", flat=True))
    if amount <= 0:
        raise ValueError("Le montant doit être positif.")
    if amount > debt - pending:
        raise ValueError(f"Vous ne devez que {gnf(max(0, debt - pending))} (versements en attente déduits).")
    deposit = CashDeposit.objects.create(user=user, amount=amount, method=method, reference=reference)
    audit(user, "deposit.declared", deposit, {"amount": amount, "method": method, "reference": reference})
    notify_team("Versement d'espèces à vérifier", f"{user.name} : {gnf(amount)} ({deposit.get_method_display()}, réf. {reference}).")
    return deposit


@transaction.atomic
def process_deposit(deposit, admin, confirmed, note=""):
    deposit = CashDeposit.objects.select_for_update().get(pk=deposit.pk)
    if deposit.status != CashDeposit.Status.PENDING:
        return deposit
    deposit.status = CashDeposit.Status.CONFIRMED if confirmed else CashDeposit.Status.REJECTED
    deposit.admin_note = note
    deposit.processed_by = admin
    deposit.processed_at = timezone.now()
    deposit.save()
    audit(admin, "deposit.confirmed" if confirmed else "deposit.rejected", deposit, {"amount": deposit.amount, "note": note})
    if confirmed:
        LedgerEntry.objects.create(
            user=deposit.user, kind=LedgerEntry.Kind.CASH_DEPOSIT, amount=deposit.amount, deposit=deposit,
            note=f"{deposit.get_method_display()} – réf. {deposit.reference}",
        )
        notify(deposit.user, "wallet", "Versement confirmé ✅", f"{gnf(deposit.amount)} bien reçus. Merci !", "/portefeuille")
    else:
        notify(deposit.user, "wallet", "Versement non reconnu",
               note or "Nous n'avons pas trouvé ce versement. Vérifiez la référence.", "/portefeuille")
    return deposit
