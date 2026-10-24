"""
Paiement par carte (diaspora) via Stripe Checkout, en euros.
Le montant GNF est converti avec le taux EXCHANGE_RATES["EUR"].
Documentation : https://docs.stripe.com/api/checkout/sessions
"""

import requests
from django.conf import settings

from .base import BaseProvider, PaymentError, PaymentInit

API = "https://api.stripe.com/v1"


def gnf_to_eur_cents(amount):
    return max(50, round(amount / settings.EXCHANGE_RATES["EUR"] * 100))


class StripeProvider(BaseProvider):
    def __init__(self):
        self.key = settings.STRIPE["SECRET_KEY"]
        if not self.key:
            raise PaymentError("Le paiement par carte n'est pas encore configuré.")

    def initiate(self, payment, return_url, cancel_url, notify_url):
        r = requests.post(
            f"{API}/checkout/sessions",
            auth=(self.key, ""),
            data={
                "mode": "payment",
                "success_url": return_url,
                "cancel_url": cancel_url,
                "client_reference_id": str(payment.reference),
                "line_items[0][quantity]": 1,
                "line_items[0][price_data][currency]": "eur",
                "line_items[0][price_data][unit_amount]": gnf_to_eur_cents(payment.amount),
                "line_items[0][price_data][product_data][name]": f"GuinéeMall – commande #{payment.order_id}",
            },
            timeout=20,
        )
        data = r.json()
        if r.status_code != 200:
            raise PaymentError(data.get("error", {}).get("message", "Paiement par carte indisponible."))
        return PaymentInit(redirect_url=data["url"], provider_reference=data["id"], raw={"id": data["id"]})

    def check_status(self, payment):
        r = requests.get(f"{API}/checkout/sessions/{payment.provider_reference}", auth=(self.key, ""), timeout=20)
        data = r.json()
        if data.get("payment_status") == "paid":
            return "success"
        return "failed" if data.get("status") == "expired" else "pending"
