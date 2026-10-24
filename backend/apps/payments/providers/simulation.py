"""
Fournisseur simulé pour le développement : renvoie vers une page de GuinéeMall
où l'on choisit « paiement réussi » ou « paiement refusé ».
"""

from django.conf import settings

from .base import BaseProvider, PaymentInit


class SimulationProvider(BaseProvider):
    def __init__(self, name):
        self.name = name

    def initiate(self, payment, return_url, cancel_url, notify_url):
        return PaymentInit(
            redirect_url=f"{settings.FRONTEND_URL}/paiement/simulation/{payment.reference}",
            provider_reference=f"SIM-{payment.reference}",
            message="Mode simulation : aucun argent réel n'est débité.",
        )

    def check_status(self, payment):
        return payment.status if payment.status != "expired" else "failed"
