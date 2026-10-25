from django.conf import settings
from django.db import models

from apps.core.models import SoftDeleteModel, TimeStampedModel, unique_slug
from apps.core.storage import private_storage
from apps.core.uploads import validate_image_5mb
from apps.core.phone import PhoneField


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
    logo = models.ImageField(upload_to="vendors/logos/", blank=True, validators=[validate_image_5mb])
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    whatsapp = PhoneField("numéro WhatsApp", blank=True)

    # Badge « Vendeur vérifié » : pièce d'identité + téléphone contrôlés par un admin
    is_verified = models.BooleanField("vérifié", default=False)
    verified_at = models.DateTimeField(null=True, blank=True)

    # Abonnement premium : commission réduite et mise en avant
    is_premium = models.BooleanField("premium", default=False)
    commission_rate = models.DecimalField(
        "commission", max_digits=4, decimal_places=3, null=True, blank=True,
        help_text="Vide = taux par défaut de la plateforme (ex. 0.080 pour 8 %)",
    )

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

    @property
    def effective_commission_rate(self):
        return float(self.commission_rate) if self.commission_rate is not None else settings.PLATFORM_COMMISSION_RATE


class VerificationRequest(TimeStampedModel):
    """Demande du badge « Vendeur vérifié » : pièce d'identité contrôlée par un admin."""

    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        APPROVED = "approved", "Acceptée"
        REJECTED = "rejected", "Refusée"

    class DocumentType(models.TextChoices):
        ID_CARD = "id_card", "Carte d'identité"
        PASSPORT = "passport", "Passeport"
        RCCM = "rccm", "Registre du commerce (RCCM)"

    vendor = models.ForeignKey(Vendor, on_delete=models.CASCADE, related_name="verification_requests")
    document_type = models.CharField(max_length=10, choices=DocumentType.choices)
    document_number = models.CharField("numéro du document", max_length=50)
    # Stockage privé : ces fichiers ne sont jamais accessibles par une adresse publique
    document_front = models.ImageField(
        "recto", upload_to="verifications/", storage=private_storage, validators=[validate_image_5mb]
    )
    document_back = models.ImageField(
        "verso", upload_to="verifications/", storage=private_storage, blank=True, validators=[validate_image_5mb]
    )
    shop_photo = models.ImageField(
        "photo de la boutique", upload_to="verifications/", storage=private_storage, blank=True,
        validators=[validate_image_5mb],
    )
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    admin_note = models.CharField("motif", max_length=255, blank=True)
    reviewed_by = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+"
    )
    reviewed_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "demande de vérification"
        ordering = ["-created_at"]
