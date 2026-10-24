from django.conf import settings

from .base import PaymentError, PaymentInit
from .card import StripeProvider
from .mtn_momo import MtnMomoProvider
from .orange_money import OrangeMoneyProvider
from .simulation import SimulationProvider

__all__ = ["PaymentError", "PaymentInit", "get_provider"]

LIVE_PROVIDERS = {
    "orange_money": OrangeMoneyProvider,
    "mtn_momo": MtnMomoProvider,
    "card": StripeProvider,
}


def get_provider(name):
    if settings.PAYMENT_MODE != "live":
        return SimulationProvider(name)
    return LIVE_PROVIDERS[name]()
