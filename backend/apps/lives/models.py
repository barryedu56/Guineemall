from django.conf import settings
from django.db import models

from apps.core.models import TimeStampedModel


class LiveSession(TimeStampedModel):
    """
    Vente en direct : la vidéo est diffusée sur Facebook, YouTube ou TikTok (gratuit,
    sans serveur vidéo à gérer) et GuinéeMall affiche le direct avec les produits
    épinglés et un chat en temps réel pour commander sans quitter la page.
    """

    class Status(models.TextChoices):
        SCHEDULED = "scheduled", "Programmé"
        LIVE = "live", "En direct"
        ENDED = "ended", "Terminé"

    vendor = models.ForeignKey("vendors.Vendor", on_delete=models.CASCADE, related_name="lives")
    title = models.CharField("titre", max_length=150)
    description = models.TextField(blank=True)
    stream_url = models.URLField("lien du direct", help_text="Lien YouTube, Facebook ou TikTok")
    starts_at = models.DateTimeField("début prévu")
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.SCHEDULED)
    products = models.ManyToManyField("catalog.Product", blank=True, related_name="lives")
    pinned_product = models.ForeignKey(
        "catalog.Product", on_delete=models.SET_NULL, null=True, blank=True, related_name="+",
        help_text="Produit présenté en ce moment",
    )

    class Meta:
        verbose_name = "direct"
        ordering = ["-starts_at"]

    def __str__(self):
        return self.title


class LiveComment(models.Model):
    live = models.ForeignKey(LiveSession, on_delete=models.CASCADE, related_name="comments")
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="+")
    text = models.CharField(max_length=300)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["created_at"]
