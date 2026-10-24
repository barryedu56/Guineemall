import uuid

from django.db import models

from apps.core.models import TimeStampedModel
from apps.core.phone import PhoneField


class Payment(TimeStampedModel):
    """Tentative de paiement en ligne d'une commande (un client peut réessayer)."""

    class Provider(models.TextChoices):
        ORANGE_MONEY = "orange_money", "Orange Money"
        MTN_MOMO = "mtn_momo", "MTN Mobile Money"
        CARD = "card", "Carte bancaire"

    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        SUCCESS = "success", "Réussi"
        FAILED = "failed", "Échoué"
        EXPIRED = "expired", "Expiré"

    order = models.ForeignKey("orders.Order", on_delete=models.CASCADE, related_name="payments")
    provider = models.CharField(max_length=20, choices=Provider.choices)
    reference = models.UUIDField(default=uuid.uuid4, unique=True, editable=False)
    amount = models.PositiveBigIntegerField("montant (GNF)")
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    payer_phone = PhoneField("numéro qui paie", blank=True)
    # Données renvoyées par l'opérateur
    provider_reference = models.CharField(max_length=150, blank=True)
    pay_token = models.CharField(max_length=255, blank=True)
    notif_token = models.CharField(max_length=255, blank=True)
    payment_url = models.URLField(max_length=500, blank=True)
    raw_response = models.JSONField(default=dict, blank=True)
    failure_reason = models.CharField(max_length=255, blank=True)

    class Meta:
        verbose_name = "paiement"
        ordering = ["-created_at"]
        indexes = [models.Index(fields=["status", "created_at"])]

    def __str__(self):
        return f"{self.get_provider_display()} – commande #{self.order_id} – {self.get_status_display()}"
