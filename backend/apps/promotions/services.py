from django.conf import settings
from django.db.models import Q
from django.utils import timezone

from .models import Coupon


class CouponError(Exception):
    pass


def find_valid_coupon(code, user, lines):
    """
    Vérifie un code promo pour un panier. `lines` : liste de (product, quantity).
    Renvoie (coupon, montant éligible).
    """
    now = timezone.now()
    coupon = (
        Coupon.objects.filter(code=code.upper().strip(), is_active=True, starts_at__lte=now)
        .filter(Q(ends_at__isnull=True) | Q(ends_at__gte=now))
        .first()
    )
    if coupon is None:
        raise CouponError("Ce code promo n'existe pas ou a expiré.")
    if coupon.user_id and coupon.user_id != user.id:
        raise CouponError("Ce bon de réduction est personnel.")
    if coupon.usage_limit is not None and coupon.used_count >= coupon.usage_limit:
        raise CouponError("Ce code promo a atteint son nombre maximum d'utilisations.")
    if coupon.redemptions.filter(user=user).count() >= coupon.per_user_limit:
        raise CouponError("Vous avez déjà utilisé ce code promo.")

    eligible = sum(
        p.price * qty for p, qty in lines if coupon.vendor_id is None or p.vendor_id == coupon.vendor_id
    )
    if eligible == 0:
        raise CouponError("Ce code promo ne s'applique à aucun article de votre panier.")
    if eligible < coupon.min_amount:
        raise CouponError(f"Ce code promo nécessite un minimum de {coupon.min_amount:,} GNF d'achat.".replace(",", " "))
    return coupon, eligible


def create_referral_coupon(user, description):
    """Bon personnel offert dans le cadre du parrainage."""
    return Coupon.objects.create(
        code=f"PARRAIN-{user.referral_code}-{Coupon.objects.filter(user=user).count() + 1}",
        type=Coupon.Type.FIXED,
        value=settings.REFERRAL_REWARD_GNF,
        min_amount=settings.REFERRAL_REWARD_GNF * 5,
        user=user,
        description=description,
    )
