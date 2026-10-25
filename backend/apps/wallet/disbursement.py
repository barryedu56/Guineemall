"""
Envoi automatique de l'argent (retraits des vendeurs et livreurs, avoirs rendus aux clients).

PAYOUT_MODE="live" + clés de l'opérateur : l'argent part tout seul depuis le compte GuinéeMall, et son
statut est suivi (tâche check_transfers). Sinon, la demande attend dans le back-office et l'équipe
envoie l'argent à la main avant de saisir la référence.

- MTN : API « Disbursement » (transfer), contrat distinct de la Collection.
- Orange Money : l'envoi vers un client se fait par un contrat de partenaire propre à Orange Guinée ;
  tant qu'il n'est pas signé et documenté, les retraits Orange restent manuels.
"""

import uuid

import requests
from django.conf import settings
from django.core.cache import cache

from apps.notifications.services import notify_team
from apps.notifications.whatsapp import normalize_phone

TIMEOUT = 20


class TransferError(Exception):
    pass


class MtnDisbursement:
    def __init__(self):
        self.config = {**settings.MTN_MOMO_DISBURSEMENT, "API_URL": settings.MTN_MOMO["API_URL"],
                       "TARGET_ENVIRONMENT": settings.MTN_MOMO["TARGET_ENVIRONMENT"], "CURRENCY": settings.MTN_MOMO["CURRENCY"]}

    @classmethod
    def configured(cls):
        c = settings.MTN_MOMO_DISBURSEMENT
        return bool(c["SUBSCRIPTION_KEY"] and c["API_USER"] and c["API_KEY"])

    def _headers(self, **extra):
        return {"Ocp-Apim-Subscription-Key": self.config["SUBSCRIPTION_KEY"],
                "X-Target-Environment": self.config["TARGET_ENVIRONMENT"], **extra}

    def _token(self):
        token = cache.get("mtn_disbursement_token")
        if token:
            return token
        r = requests.post(f"{self.config['API_URL']}/disbursement/token/", auth=(self.config["API_USER"], self.config["API_KEY"]),
                          headers={"Ocp-Apim-Subscription-Key": self.config["SUBSCRIPTION_KEY"]}, timeout=TIMEOUT)
        if r.status_code != 200:
            raise TransferError("MTN indisponible (jeton refusé).")
        data = r.json()
        cache.set("mtn_disbursement_token", data["access_token"], int(data.get("expires_in", 3600)) - 60)
        return data["access_token"]

    def transfer(self, reference, amount, phone, note):
        r = requests.post(
            f"{self.config['API_URL']}/disbursement/v1_0/transfer",
            headers=self._headers(Authorization=f"Bearer {self._token()}", **{"X-Reference-Id": reference}),
            json={"amount": str(amount), "currency": self.config["CURRENCY"], "externalId": reference,
                  "payee": {"partyIdType": "MSISDN", "partyId": normalize_phone(phone)},
                  "payerMessage": note, "payeeNote": note},
            timeout=TIMEOUT,
        )
        if r.status_code != 202:
            raise TransferError(f"Envoi MTN refusé ({r.status_code}).")

    def status(self, reference):
        r = requests.get(f"{self.config['API_URL']}/disbursement/v1_0/transfer/{reference}",
                         headers=self._headers(Authorization=f"Bearer {self._token()}"), timeout=TIMEOUT)
        status = (r.json() if r.content else {}).get("status", "")
        return {"SUCCESSFUL": "success", "FAILED": "failed", "REJECTED": "failed"}.get(status, "pending")


DISBURSERS = {"mtn_momo": MtnDisbursement}


def get_disburser(method):
    cls = DISBURSERS.get(method)
    if settings.PAYOUT_MODE != "live" or cls is None or not cls.configured():
        return None
    return cls()


def start_transfer(obj, note):
    """
    obj : PayoutRequest ou CreditRefund en attente. Lance l'envoi automatique si possible.
    Renvoie True si l'argent est parti (suivi ensuite par check_transfers), False si l'équipe doit le faire.
    """
    disburser = get_disburser(obj.method)
    if disburser is None or obj.status != obj.Status.PENDING:
        return False
    obj.provider_reference = str(uuid.uuid4())
    obj.status = obj.Status.SENDING
    obj.save(update_fields=["provider_reference", "status", "updated_at"])
    try:
        disburser.transfer(obj.provider_reference, obj.amount, obj.phone, note)
    except (TransferError, requests.RequestException) as e:
        obj.status = obj.Status.PENDING
        obj.save(update_fields=["status", "updated_at"])
        notify_team("Envoi automatique impossible", f"{obj} : {e}. À envoyer à la main.")
        return False
    return True


def check_transfers():
    """Suit les envois automatiques en cours : payé, ou remis dans la file manuelle en cas d'échec."""
    from .credit import process_cash_out
    from .models import CreditRefund, PayoutRequest
    from .services import process_payout

    done = 0
    for model, finish in ((PayoutRequest, process_payout), (CreditRefund, process_cash_out)):
        for obj in model.objects.filter(status=model.Status.SENDING):
            disburser = get_disburser(obj.method)
            try:
                status = disburser.status(obj.provider_reference) if disburser else "pending"
            except (TransferError, requests.RequestException):
                continue
            if status == "success":
                finish(obj, None, paid=True, reference=obj.provider_reference, automatic=True)
                done += 1
            elif status == "failed":
                model.objects.filter(pk=obj.pk, status=model.Status.SENDING).update(status=model.Status.PENDING)
                notify_team("Envoi automatique échoué", f"{obj} : l'opérateur a refusé l'envoi. À faire à la main.")
    return done
