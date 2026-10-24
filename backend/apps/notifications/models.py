from django.conf import settings
from django.db import models


class Notification(models.Model):
    class Kind(models.TextChoices):
        ORDER = "order", "Commande"
        MESSAGE = "message", "Message"
        PAYMENT = "payment", "Paiement"
        SHOP = "shop", "Boutique"
        REVIEW = "review", "Avis"
        WALLET = "wallet", "Portefeuille"
        PROMO = "promo", "Promotion"
        DELIVERY = "delivery", "Livraison"

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="notifications")
    kind = models.CharField(max_length=20, choices=Kind.choices)
    title = models.CharField(max_length=200)
    body = models.TextField(blank=True)
    link = models.CharField("lien dans le site", max_length=255, blank=True)
    read_at = models.DateTimeField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "notification"
        ordering = ["-created_at"]
        indexes = [models.Index(fields=["user", "read_at"])]

    def __str__(self):
        return f"{self.user} : {self.title}"
