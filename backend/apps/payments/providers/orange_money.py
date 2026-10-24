"""
Orange Money Web Payment (API Orange Developer).

Parcours : jeton OAuth → création du paiement (renvoie payment_url) → le client paie
sur la page Orange → Orange appelle notif_url avec le statut.
Documentation : https://developer.orange.com/apis/om-webpay
Les URL et la devise (« GNF », ou « OUV » en environnement de test) sont à confirmer
avec Orange Guinée lors de l'ouverture du compte marchand.
"""

import requests
from django.conf import settings
from django.core.cache import cache

from .base import BaseProvider, PaymentError, PaymentInit

TIMEOUT = 20


class OrangeMoneyProvider(BaseProvider):
    def __init__(self):
        self.config = settings.ORANGE_MONEY
        if not (self.config["CLIENT_ID"] and self.config["MERCHANT_KEY"]):
            raise PaymentError("Orange Money n'est pas encore configuré.")

    @property
    def base(self):
        return f"{self.config['API_URL']}/orange-money-webpay/{self.config['COUNTRY']}/v1"

    def _token(self):
        token = cache.get("orange_money_token")
        if token:
            return token
        r = requests.post(
            f"{self.config['API_URL']}/oauth/v3/token",
            auth=(self.config["CLIENT_ID"], self.config["CLIENT_SECRET"]),
            data={"grant_type": "client_credentials"},
            timeout=TIMEOUT,
        )
        if r.status_code != 200:
            raise PaymentError("Orange Money est indisponible pour le moment.")
        data = r.json()
        cache.set("orange_money_token", data["access_token"], int(data.get("expires_in", 3600)) - 60)
        return data["access_token"]

    def initiate(self, payment, return_url, cancel_url, notify_url):
        r = requests.post(
            f"{self.base}/webpayment",
            headers={"Authorization": f"Bearer {self._token()}", "Accept": "application/json"},
            json={
                "merchant_key": self.config["MERCHANT_KEY"],
                "currency": self.config["CURRENCY"],
                "order_id": str(payment.reference),
                "amount": payment.amount,
                "return_url": return_url,
                "cancel_url": cancel_url,
                "notif_url": notify_url,
                "lang": "fr",
                "reference": "GuineeMall",
            },
            timeout=TIMEOUT,
        )
        data = r.json() if r.content else {}
        if r.status_code not in (200, 201) or "payment_url" not in data:
            raise PaymentError(data.get("message") or "Le paiement Orange Money n'a pas pu être lancé.")
        return PaymentInit(
            redirect_url=data["payment_url"],
            pay_token=data.get("pay_token", ""),
            notif_token=data.get("notif_token", ""),
            raw=data,
        )

    def check_status(self, payment):
        r = requests.post(
            f"{self.base}/transactionstatus",
            headers={"Authorization": f"Bearer {self._token()}", "Accept": "application/json"},
            json={"order_id": str(payment.reference), "amount": payment.amount, "pay_token": payment.pay_token},
            timeout=TIMEOUT,
        )
        status = (r.json() if r.content else {}).get("status", "")
        return {"SUCCESS": "success", "FAILED": "failed", "EXPIRED": "failed"}.get(status, "pending")
