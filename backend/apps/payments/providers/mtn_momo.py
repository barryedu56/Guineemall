"""
MTN Mobile Money – API Collection (« Request to Pay »).

Parcours : jeton → requesttopay (le client reçoit une demande à valider sur son téléphone)
→ on interroge le statut (ou MTN appelle l'URL de rappel).
Documentation : https://momodeveloper.mtn.com/api-documentation
"""

import requests
from django.conf import settings
from django.core.cache import cache

from apps.notifications.whatsapp import normalize_phone

from .base import BaseProvider, PaymentError, PaymentInit

TIMEOUT = 20


class MtnMomoProvider(BaseProvider):
    def __init__(self):
        self.config = settings.MTN_MOMO
        if not (self.config["SUBSCRIPTION_KEY"] and self.config["API_USER"]):
            raise PaymentError("MTN Mobile Money n'est pas encore configuré.")

    def _headers(self, **extra):
        return {
            "Ocp-Apim-Subscription-Key": self.config["SUBSCRIPTION_KEY"],
            "X-Target-Environment": self.config["TARGET_ENVIRONMENT"],
            **extra,
        }

    def _token(self):
        token = cache.get("mtn_momo_token")
        if token:
            return token
        r = requests.post(
            f"{self.config['API_URL']}/collection/token/",
            auth=(self.config["API_USER"], self.config["API_KEY"]),
            headers={"Ocp-Apim-Subscription-Key": self.config["SUBSCRIPTION_KEY"]},
            timeout=TIMEOUT,
        )
        if r.status_code != 200:
            raise PaymentError("MTN Mobile Money est indisponible pour le moment.")
        data = r.json()
        cache.set("mtn_momo_token", data["access_token"], int(data.get("expires_in", 3600)) - 60)
        return data["access_token"]

    def initiate(self, payment, return_url, cancel_url, notify_url):
        phone = normalize_phone(payment.payer_phone)
        if not phone:
            raise PaymentError("Indiquez le numéro MTN qui va payer.")
        r = requests.post(
            f"{self.config['API_URL']}/collection/v1_0/requesttopay",
            headers=self._headers(
                **{
                    "Authorization": f"Bearer {self._token()}",
                    "X-Reference-Id": str(payment.reference),
                    "X-Callback-Url": notify_url,
                }
            ),
            json={
                "amount": str(payment.amount),
                "currency": self.config["CURRENCY"],
                "externalId": str(payment.order_id),
                "payer": {"partyIdType": "MSISDN", "partyId": phone},
                "payerMessage": f"GuinéeMall commande #{payment.order_id}",
                "payeeNote": f"Commande #{payment.order_id}",
            },
            timeout=TIMEOUT,
        )
        if r.status_code != 202:
            raise PaymentError("La demande de paiement MTN n'a pas pu être envoyée. Vérifiez le numéro.")
        return PaymentInit(
            provider_reference=str(payment.reference),
            message="Validez le paiement sur votre téléphone MTN (composez *440# si rien ne s'affiche).",
        )

    def check_status(self, payment):
        r = requests.get(
            f"{self.config['API_URL']}/collection/v1_0/requesttopay/{payment.reference}",
            headers=self._headers(Authorization=f"Bearer {self._token()}"),
            timeout=TIMEOUT,
        )
        status = (r.json() if r.content else {}).get("status", "")
        return {"SUCCESSFUL": "success", "FAILED": "failed", "REJECTED": "failed", "TIMEOUT": "failed"}.get(
            status, "pending"
        )
