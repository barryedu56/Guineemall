from django.conf import settings
from rest_framework import permissions
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response


@api_view(["GET"])
@permission_classes([permissions.AllowAny])
def site_config(request):
    """Réglages publics utiles au front (taux de change diaspora, mode de paiement…)."""
    return Response({
        "exchange_rates": settings.EXCHANGE_RATES,
        "payment_simulation": settings.PAYMENT_MODE != "live",
        "referral_reward": settings.REFERRAL_REWARD_GNF,
        "affiliate_rate": settings.AFFILIATE_COMMISSION_RATE,
        "escrow_days": settings.ESCROW_AUTO_RELEASE_DAYS,
    })
