"""
Envoi de messages WhatsApp via l'API WhatsApp Cloud de Meta.

Hors de la fenêtre de 24 h après un message du client, WhatsApp n'accepte que des
modèles (templates) validés par Meta. On utilise un modèle générique à 2 variables :
    {{1}} = titre, {{2}} = texte
à créer dans le WhatsApp Manager sous le nom défini par WHATSAPP_TEMPLATE.

Sans jeton configuré, le message est seulement écrit dans les logs (développement).
"""

import logging
import re

import requests
from django.conf import settings

logger = logging.getLogger(__name__)

GRAPH_URL = "https://graph.facebook.com/v21.0"


def normalize_phone(phone):
    """620 11 22 33 → 224620112233 (format international sans « + »)."""
    digits = re.sub(r"\D", "", phone or "")
    if not digits:
        return ""
    if digits.startswith("00"):
        digits = digits[2:]
    if len(digits) == 9:  # numéro guinéen local
        digits = f"224{digits}"
    return digits


def send_whatsapp(phone, title, body):
    to = normalize_phone(phone)
    if not to:
        return False
    config = settings.WHATSAPP
    if not config["TOKEN"]:
        logger.info("[WhatsApp simulé] → %s : %s — %s", to, title, body)
        return True

    response = requests.post(
        f"{GRAPH_URL}/{config['PHONE_NUMBER_ID']}/messages",
        headers={"Authorization": f"Bearer {config['TOKEN']}"},
        json={
            "messaging_product": "whatsapp",
            "to": to,
            "type": "template",
            "template": {
                "name": config["TEMPLATE"],
                "language": {"code": config["LANGUAGE"]},
                "components": [
                    {"type": "body", "parameters": [{"type": "text", "text": title}, {"type": "text", "text": body[:900]}]}
                ],
            },
        },
        timeout=15,
    )
    if response.status_code >= 400:
        logger.warning("Échec WhatsApp vers %s : %s %s", to, response.status_code, response.text[:300])
        return False
    return True
