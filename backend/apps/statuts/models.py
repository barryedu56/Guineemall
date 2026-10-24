from datetime import timedelta

from django.conf import settings
from django.db import models
from django.utils import timezone


def default_expires_at():
    return timezone.now() + timedelta(hours=settings.STATUT_DURATION_HOURS)


class StatutQuerySet(models.QuerySet):
    def active(self):
        return self.filter(expires_at__gt=timezone.now())

    def expired(self):
        return self.filter(expires_at__lte=timezone.now())


class Statut(models.Model):
    """
    Statut éphémère d'une boutique (comme les statuts WhatsApp) :
    photo ou courte vidéo visible 24 h, éventuellement liée à un produit
    pour que le client puisse l'acheter directement.
    """

    class MediaType(models.TextChoices):
        IMAGE = "image", "Image"
        VIDEO = "video", "Vidéo"

    vendor = models.ForeignKey("vendors.Vendor", on_delete=models.CASCADE, related_name="statuts")
    media = models.FileField(upload_to="statuts/")
    media_type = models.CharField(max_length=10, choices=MediaType.choices, default=MediaType.IMAGE)
    caption = models.CharField("légende", max_length=280, blank=True)
    product = models.ForeignKey(
        "catalog.Product", on_delete=models.SET_NULL, null=True, blank=True, related_name="statuts"
    )
    created_at = models.DateTimeField(auto_now_add=True)
    expires_at = models.DateTimeField(default=default_expires_at, db_index=True)

    objects = StatutQuerySet.as_manager()

    class Meta:
        verbose_name = "statut"
        ordering = ["-created_at"]
        indexes = [models.Index(fields=["vendor", "expires_at"])]

    def __str__(self):
        return f"Statut de {self.vendor} ({self.created_at:%d/%m %H:%M})"

    @property
    def is_active(self):
        return self.expires_at > timezone.now()


class StatutView(models.Model):
    """Qui a vu le statut (le vendeur voit la liste, comme sur WhatsApp)."""

    statut = models.ForeignKey(Statut, on_delete=models.CASCADE, related_name="views")
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="+")
    viewed_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "vue de statut"
        constraints = [
            models.UniqueConstraint(fields=["statut", "user"], name="unique_statut_view"),
        ]
