"""
Avoir GuinéeMall du client.

- Une commande payée (en ligne ou avec l'avoir) puis annulée est remboursée tout de suite en avoir.
- L'avoir se dépense à la commande suivante (seul, ou complété par un paiement en ligne).
- Le client peut demander à tout moment qu'il lui soit rendu sur Orange Money / MTN : le montant est
  réservé aussitôt, puis un admin envoie l'argent (ou refuse, et l'avoir est rendu).
"""

from django.db import transaction
from django.utils import timezone

from apps.core.audit import audit
from apps.core.formatting import gnf
from apps.notifications.services import notify, notify_team

from .models import CreditEntry, CreditRefund

balance = CreditEntry.balance


def add_credit(user, amount, kind, order=None, note=""):
    if amount:
        return CreditEntry.objects.create(user=user, kind=kind, amount=amount, order=order, note=note)
    return None


def refund_to_credit(order, amount, reason):
    """Rembourse une commande (ou une partie) en avoir et prévient le client."""
    if amount <= 0:
        return None
    entry = add_credit(order.user, amount, CreditEntry.Kind.REFUND, order, f"Commande #{order.id} : {reason}")
    notify(order.user, "wallet", f"{gnf(amount)} ajoutés à votre avoir",
           f"Commande #{order.id} : {reason}. Utilisez-le sur votre prochaine commande, "
           "ou demandez-le sur votre Mobile Money.", "/portefeuille")
    return entry


@transaction.atomic
def request_cash_out(user, amount, method, phone):
    from apps.accounts.models import User

    User.objects.select_for_update().get(pk=user.pk)  # deux demandes simultanées ne dépassent pas le solde
    available = balance(user)
    if amount <= 0:
        raise ValueError("Le montant doit être positif.")
    if amount > available:
        raise ValueError(f"Votre avoir est de {gnf(available)}.")
    refund = CreditRefund.objects.create(user=user, amount=amount, method=method, phone=phone)
    CreditEntry.objects.create(user=user, kind=CreditEntry.Kind.CASH_OUT, amount=-amount, refund=refund,
                               note=f"Vers {refund.get_method_display()} {phone}")
    audit(user, "credit.cash_out_requested", refund, {"amount": amount, "method": method})
    notify_team("Avoir à rembourser", f"{user.name} : {gnf(amount)} vers {refund.get_method_display()} {phone}.",
                "/administration/avoirs")
    return refund


@transaction.atomic
def process_cash_out(refund, admin, paid, reference="", note=""):
    refund = CreditRefund.objects.select_for_update().get(pk=refund.pk)
    if refund.status != CreditRefund.Status.PENDING:
        return refund
    refund.status = CreditRefund.Status.PAID if paid else CreditRefund.Status.REJECTED
    refund.transaction_reference = reference
    refund.admin_note = note
    refund.processed_by = admin
    refund.processed_at = timezone.now()
    refund.save()
    audit(admin, "credit.cash_out_paid" if paid else "credit.cash_out_rejected", refund,
          {"amount": refund.amount, "reference": reference, "note": note})
    if paid:
        notify(refund.user, "wallet", "Avoir remboursé",
               f"{gnf(refund.amount)} envoyés sur votre {refund.get_method_display()} {refund.phone}.", "/portefeuille")
    else:
        CreditEntry.objects.create(user=refund.user, kind=CreditEntry.Kind.CASH_OUT_CANCELLED, amount=refund.amount,
                                   refund=refund, note=note)
        notify(refund.user, "wallet", "Remboursement refusé : avoir rendu",
               note or "Contactez GuinéeMall pour plus d'informations.", "/portefeuille")
    return refund
