from django.conf import settings
from django.db import models

from apps.core.models import SoftDeleteModel, TimeStampedModel, unique_slug


class Vendor(TimeStampedModel, SoftDeleteModel):
    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        APPROVED = "approved", "Approuvé"
        SUSPENDED = "suspended", "Suspendu"

    user = models.OneToOneField(
        settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="vendor"
    )
    shop_name = models.CharField("nom de la boutique", max_length=255)
    slug = models.SlugField(max_length=255, unique=True, blank=True)
    description = models.TextField(blank=True)
    logo = models.ImageField(upload_to="vendors/logos/", blank=True)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    whatsapp = models.CharField("numéro WhatsApp", max_length=20, blank=True)

    # Badge « Vendeur vérifié » : pièce d'identité + téléphone contrôlés par un admin
    is_verified = models.BooleanField("vérifié", default=False)
    verified_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "vendeur"
        indexes = [models.Index(fields=["status"])]

    def __str__(self):
        return self.shop_name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = unique_slug(self, self.shop_name, fallback="boutique")
        super().save(*args, **kwargs)

    @property
    def is_approved(self):
        return self.status == self.Status.APPROVED
