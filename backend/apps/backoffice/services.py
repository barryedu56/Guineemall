"""
Décisions de l'équipe, communes au back-office du site et à l'admin Django :
chaque décision prévient la personne concernée et est inscrite au journal d'audit.
"""

from django.db import transaction
from django.utils import timezone

from apps.accounts.password import revoke_all_sessions
from apps.core.audit import audit
from apps.notifications.services import notify
from apps.orders.models import Order, VendorOrder
from apps.vendors.models import Vendor, VerificationRequest


def approve_vendor(actor, vendor):
    if vendor.status == Vendor.Status.APPROVED:
        return vendor
    vendor.status = Vendor.Status.APPROVED
    vendor.save(update_fields=["status", "updated_at"])
    audit(actor, "vendor.approved", vendor)
    notify(vendor.user, "shop", "Votre boutique est en ligne 🎉", "Ajoutez vos produits et publiez votre premier statut.", "/vendeur")
    return vendor


def suspend_vendor(actor, vendor, reason=""):
    if vendor.status == Vendor.Status.SUSPENDED:
        return vendor
    vendor.status = Vendor.Status.SUSPENDED
    vendor.save(update_fields=["status", "updated_at"])
    audit(actor, "vendor.suspended", vendor, {"reason": reason})
    notify(vendor.user, "shop", "Boutique suspendue", reason or "Contactez le service client pour en savoir plus.", "/vendeur")
    return vendor


@transaction.atomic
def decide_verification(actor, request, approved, note=""):
    request = VerificationRequest.objects.select_for_update().select_related("vendor__user").get(pk=request.pk)
    if request.status != VerificationRequest.Status.PENDING:
        return request
    request.status = VerificationRequest.Status.APPROVED if approved else VerificationRequest.Status.REJECTED
    request.admin_note = note or request.admin_note
    request.reviewed_by = actor
    request.reviewed_at = timezone.now()
    request.save()
    vendor = request.vendor
    audit(actor, "verification.approved" if approved else "verification.rejected", vendor, {"note": request.admin_note})
    if approved:
        vendor.is_verified = True
        vendor.verified_at = timezone.now()
        vendor.save(update_fields=["is_verified", "verified_at", "updated_at"])
        notify(vendor.user, "shop", "Badge « Vendeur vérifié » obtenu ✅",
               "Le badge apparaît maintenant sur votre boutique et vos produits.", "/vendeur/boutique")
    else:
        notify(vendor.user, "shop", "Vérification refusée",
               request.admin_note or "Vos documents n'ont pas pu être validés. Vous pouvez renvoyer une demande.",
               "/vendeur/boutique")
    return request


def revoke_badge(actor, vendor, reason=""):
    vendor.is_verified = False
    vendor.verified_at = None
    vendor.save(update_fields=["is_verified", "verified_at", "updated_at"])
    audit(actor, "vendor.badge_revoked", vendor, {"reason": reason})
    notify(vendor.user, "shop", "Badge « Vendeur vérifié » retiré", reason or "Contactez le service client.", "/vendeur/boutique")


def set_rider_approval(actor, rider, approved):
    if rider.is_approved == approved:
        return rider
    rider.is_approved = approved
    rider.save(update_fields=["is_approved", "updated_at"])
    audit(actor, "rider.approved" if approved else "rider.suspended", rider)
    if approved:
        notify(rider.user, "delivery", "Compte livreur validé ✅", "Vous pouvez accepter des courses.", "/livreur")
    else:
        notify(rider.user, "delivery", "Compte livreur suspendu", "Contactez le service client pour en savoir plus.", "/livreur")
    return rider


def set_user_active(actor, user, active):
    if user.pk == actor.pk:
        raise ValueError("Vous ne pouvez pas désactiver votre propre compte.")
    user.is_active = active
    user.save(update_fields=["is_active"])
    if not active:
        revoke_all_sessions(user)  # déconnecté de tous ses appareils
    audit(actor, "user.activated" if active else "user.deactivated", user)
    return user


def reset_two_factor(actor, user):
    """Téléphone perdu : la double authentification est remise à zéro (à reconfigurer à la connexion)."""
    user.totp_secret = ""
    user.totp_enabled_at = None
    user.save(update_fields=["totp_secret", "totp_enabled_at"])
    revoke_all_sessions(user)
    audit(actor, "user.2fa_reset", user)
    return user


def mark_refunded(actor, order, reference=""):
    if order.payment_status != Order.PaymentStatus.REFUND_PENDING:
        raise ValueError("Cette commande n'attend pas de remboursement.")
    order.payment_status = Order.PaymentStatus.REFUNDED
    order.save(update_fields=["payment_status", "updated_at"])
    audit(actor, "order.refunded", order, {"reference": reference})
    notify(order.user, "payment", f"Remboursement de la commande #{order.id}",
           "Votre remboursement a été effectué.", f"/commandes/{order.id}")
    return order


def unlock_delivery_code(actor, vendor_order):
    audit(actor, "delivery_code.unlocked", vendor_order, {"attempts": vendor_order.delivery_code_attempts})
    VendorOrder.objects.filter(pk=vendor_order.pk).update(delivery_code_attempts=0)
