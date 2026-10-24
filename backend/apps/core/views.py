from django.conf import settings
from rest_framework import permissions
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response

from apps.delivery.models import DeliveryZone
from apps.orders.models import Order, VendorOrder
from apps.promotions.models import Coupon
from apps.vendors.models import VerificationRequest
from apps.wallet.models import PayoutRequest

from .models import SiteSettings

# Texte des boutons d'action du vendeur pour chaque statut cible
VENDOR_ACTION_LABELS = {
    "confirmed": "Confirmer",
    "preparing": "Commencer la préparation",
    "ready": "Marquer prête",
    "delivered": "Marquer livrée",
    "cancelled": "Refuser / annuler",
}

PAYMENT_DESCRIPTIONS = {
    "orange_money": "Payez depuis votre téléphone, argent protégé",
    "mtn_momo": "Validez sur votre téléphone MTN",
    "cash_on_delivery": "En espèces, à la réception du colis",
    "card": "Visa, Mastercard – paiement en euros",
}


def choices(enum):
    return [{"value": value, "label": label} for value, label in enum.choices]


def file_url(request, field):
    return request.build_absolute_uri(field.url) if field else None


@api_view(["GET"])
@permission_classes([permissions.AllowAny])
def site_config(request):
    """
    Configuration publique du site : textes et coordonnées (modifiables dans l'admin),
    listes de choix, villes et communes livrées, modes de paiement actifs.
    Le front n'a aucune de ces valeurs écrite en dur.
    """
    site = SiteSettings.load()
    payment_labels = dict(Order.PaymentMethod.choices)

    locations = {}
    for zone in DeliveryZone.objects.filter(is_active=True).order_by("city", "commune"):
        communes = locations.setdefault(zone.city, [])
        if zone.commune:
            communes.append(zone.commune)

    return Response({
        "site": {
            "name": site.site_name,
            "tagline": site.tagline,
            "announcement": site.announcement,
            "hero_title": site.hero_title,
            "hero_subtitle": site.hero_subtitle,
            "hero_image": file_url(request, site.hero_image),
            "contact_phone": site.contact_phone,
            "contact_whatsapp": site.contact_whatsapp,
            "contact_email": site.contact_email,
            "address": site.address,
            "social": {k: v for k, v in (
                ("facebook", site.facebook_url), ("instagram", site.instagram_url), ("tiktok", site.tiktok_url),
            ) if v},
        },
        "payment_methods": [
            {"value": m, "label": payment_labels[m], "description": PAYMENT_DESCRIPTIONS[m]}
            for m in site.enabled_payment_methods()
        ],
        "delivery_locations": [{"city": city, "communes": communes} for city, communes in locations.items()],
        "choices": {
            "order_status": choices(Order.Status),
            "payment_method": choices(Order.PaymentMethod),
            "vendor_order_status": [
                {**c, "action": VENDOR_ACTION_LABELS.get(c["value"], "")} for c in choices(VendorOrder.Status)
            ],
            "document_type": choices(VerificationRequest.DocumentType),
            "payout_method": choices(PayoutRequest.Method),
            "coupon_type": choices(Coupon.Type),
            "product_sort": [
                {"value": "-created_at", "label": "Plus récents"},
                {"value": "price", "label": "Prix croissant"},
                {"value": "-price", "label": "Prix décroissant"},
            ],
        },
        "exchange_rates": settings.EXCHANGE_RATES,
        "payment_simulation": settings.PAYMENT_MODE != "live",
        "referral_reward": settings.REFERRAL_REWARD_GNF,
        "affiliate_rate": settings.AFFILIATE_COMMISSION_RATE,
        "commission_rate": settings.PLATFORM_COMMISSION_RATE,
        "escrow_days": settings.ESCROW_AUTO_RELEASE_DAYS,
        "unpaid_timeout_minutes": settings.UNPAID_ORDER_TIMEOUT_MINUTES,
        "page_size": 20,
    })
