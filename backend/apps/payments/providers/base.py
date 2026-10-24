from dataclasses import dataclass, field


class PaymentError(Exception):
    """Erreur de l'opérateur, affichable au client."""


@dataclass
class PaymentInit:
    """
    Résultat du lancement d'un paiement :
    - redirect_url : page de l'opérateur où le client valide (Orange Money, carte) ;
    - sinon le client valide sur son téléphone (MTN : notification USSD) et on vérifie le statut.
    """

    redirect_url: str = ""
    provider_reference: str = ""
    pay_token: str = ""
    notif_token: str = ""
    message: str = ""
    raw: dict = field(default_factory=dict)


class BaseProvider:
    def initiate(self, payment, return_url, cancel_url, notify_url):
        raise NotImplementedError

    def check_status(self, payment):
        """Renvoie 'success', 'failed' ou 'pending' en interrogeant l'opérateur."""
        raise NotImplementedError
