"""Numéro de retrait vérifié : seul numéro vers lequel l'argent d'un utilisateur peut partir."""

from datetime import timedelta

from django.conf import settings
from django.db import transaction
from django.utils import timezone

from apps.core.audit import audit
from apps.notifications.services import notify, notify_team

from .models import PayoutAccount


@transaction.atomic
def set_account(user, method, phone, holder_name, password):
    """Enregistre ou change le numéro de retrait. Le mot de passe est redemandé (compte volé, session oubliée…)."""
    if not user.check_password(password or ""):
        raise ValueError("Mot de passe incorrect.")
    account = PayoutAccount.objects.select_for_update().filter(user=user).first()
    if account and (account.method, account.phone, account.holder_name) == (method, phone, holder_name):
        return account
    changed = account is not None and account.status == PayoutAccount.Status.VERIFIED
    account = account or PayoutAccount(user=user)
    account.method, account.phone, account.holder_name = method, phone, holder_name
    account.status = PayoutAccount.Status.PENDING
    account.admin_note = ""
    account.verified_by = account.verified_at = None
    # Changement d'un numéro déjà vérifié : délai de sécurité avant tout retrait
    account.hold_until = timezone.now() + timedelta(hours=settings.PAYOUT_ACCOUNT_HOLD_HOURS) if changed else None
    account.save()
    audit(user, "payout_account.changed" if changed else "payout_account.created", account,
          {"method": method, "phone": phone, "holder_name": holder_name})
    if changed:
        notify(user, "security", "Numéro de retrait modifié",
               f"Votre argent sera désormais envoyé sur {account.get_method_display()} {phone}. "
               "Si ce n'est pas vous, changez votre mot de passe et contactez-nous immédiatement.", "/portefeuille")
    notify_team("Numéro de retrait à vérifier", f"{user.name} : {account.get_method_display()} {phone} au nom de {holder_name}.",
                "/administration/comptes-retrait")
    return account


def account_for_payout(user):
    """Le compte vers lequel envoyer l'argent, ou une erreur claire si ce n'est pas encore possible."""
    account = PayoutAccount.objects.filter(user=user).first()
    if account is None:
        raise ValueError("Enregistrez d'abord le numéro Mobile Money qui recevra votre argent.")
    if account.status == PayoutAccount.Status.REJECTED:
        raise ValueError(f"Votre numéro de retrait a été refusé{f' : {account.admin_note}' if account.admin_note else ''}. Corrigez-le.")
    if account.status != PayoutAccount.Status.VERIFIED:
        raise ValueError("Votre numéro de retrait est en cours de vérification par l'équipe. Réessayez après validation.")
    if account.hold_until and account.hold_until > timezone.now():
        raise ValueError(
            f"Numéro modifié récemment : par sécurité, retraits possibles à partir du "
            f"{timezone.localtime(account.hold_until):%d/%m à %H h %M}."
        )
    return account


@transaction.atomic
def verify_account(account, admin, ok, note=""):
    account = PayoutAccount.objects.select_for_update().get(pk=account.pk)
    account.status = PayoutAccount.Status.VERIFIED if ok else PayoutAccount.Status.REJECTED
    account.admin_note = note
    account.verified_by = admin
    account.verified_at = timezone.now()
    account.save()
    audit(admin, "payout_account.verified" if ok else "payout_account.rejected", account, {"note": note})
    if ok:
        notify(account.user, "wallet", "Numéro de retrait vérifié ✅",
               f"Vous pouvez retirer votre argent vers {account.get_method_display()} {account.phone}.", "/portefeuille")
    else:
        notify(account.user, "wallet", "Numéro de retrait refusé", note or "Vérifiez le numéro et le nom du titulaire.", "/portefeuille")
    return account
