from django.conf import settings
from django.db import models
from django.utils import timezone
from django.utils.text import slugify

from .phone import PhoneField
from .uploads import validate_image_5mb


class TimeStampedModel(models.Model):
    """Ajoute created_at / updated_at (équivalent de $table->timestamps())."""

    created_at = models.DateTimeField("créé le", auto_now_add=True)
    updated_at = models.DateTimeField("modifié le", auto_now=True)

    class Meta:
        abstract = True


class SoftDeleteQuerySet(models.QuerySet):
    def delete(self):
        return self.update(deleted_at=timezone.now())

    def alive(self):
        return self.filter(deleted_at__isnull=True)


class SoftDeleteModel(models.Model):
    """Suppression douce (équivalent de $table->softDeletes())."""

    deleted_at = models.DateTimeField("supprimé le", null=True, blank=True, editable=False)

    objects = SoftDeleteQuerySet.as_manager()

    class Meta:
        abstract = True

    def delete(self, using=None, keep_parents=False):
        self.deleted_at = timezone.now()
        self.save(update_fields=["deleted_at"])

    def hard_delete(self):
        return super().delete()

    @property
    def is_deleted(self):
        return self.deleted_at is not None


def unique_slug(instance, value, field="slug", fallback="item"):
    """Slug unique pour le modèle de l'instance : « sac-a-main », « sac-a-main-2 »…"""
    base = slugify(value) or fallback
    slug, n = base, 1
    model = type(instance)
    while model._default_manager.filter(**{field: slug}).exclude(pk=instance.pk).exists():
        n += 1
        slug = f"{base}-{n}"
    return slug


class SiteSettings(models.Model):
    """
    Réglages du site modifiables dans l'admin (une seule ligne) : textes de l'accueil,
    coordonnées, réseaux sociaux, modes de paiement proposés. Rien n'est écrit en dur dans le front.
    """

    site_name = models.CharField("nom du site", max_length=60, default="GuinéeMall")
    tagline = models.CharField("slogan", max_length=150, default="Le marché guinéen, dans votre poche.")
    announcement = models.CharField(
        "bandeau d'annonce", max_length=200, blank=True,
        default="Livraison dans tout Conakry · Paiement protégé jusqu'à la réception",
    )
    hero_title = models.CharField("titre de l'accueil", max_length=150, default="Le marché guinéen, dans votre poche.")
    hero_subtitle = models.TextField(
        "texte de l'accueil",
        default="Achetez auprès de boutiques de Conakry et de toute la Guinée. Payez par Orange Money, MTN ou à la livraison.",
    )
    hero_image = models.ImageField("image de l'accueil", upload_to="site/", blank=True, validators=[validate_image_5mb])
    contact_phone = PhoneField("téléphone", blank=True)
    contact_whatsapp = PhoneField("WhatsApp du service client", blank=True)
    contact_email = models.EmailField("email", blank=True)
    address = models.CharField("adresse", max_length=200, blank=True, default="Conakry, Guinée")
    facebook_url = models.URLField(blank=True)
    instagram_url = models.URLField(blank=True)
    tiktok_url = models.URLField(blank=True)
    # Modes de paiement proposés au client
    enable_cash_on_delivery = models.BooleanField("paiement à la livraison", default=True)
    enable_orange_money = models.BooleanField("Orange Money", default=True)
    enable_mtn_momo = models.BooleanField("MTN Mobile Money", default=True)
    enable_card = models.BooleanField("carte bancaire (diaspora)", default=True)

    class Meta:
        verbose_name = "réglages du site"
        verbose_name_plural = "réglages du site"

    def __str__(self):
        return "Réglages du site"

    def save(self, *args, **kwargs):
        self.pk = 1
        super().save(*args, **kwargs)
        from django.core.cache import cache

        cache.delete("site-settings")

    @classmethod
    def load(cls):
        from django.core.cache import cache

        settings = cache.get("site-settings")
        if settings is None:
            settings, _ = cls.objects.get_or_create(pk=1)
            cache.set("site-settings", settings, 300)
        return settings

    def enabled_payment_methods(self):
        return [
            method for method, enabled in (
                ("orange_money", self.enable_orange_money),
                ("mtn_momo", self.enable_mtn_momo),
                ("cash_on_delivery", self.enable_cash_on_delivery),
                ("card", self.enable_card),
            ) if enabled
        ]


class AuditLog(models.Model):
    """
    Journal des actions sensibles (argent, validations, accès aux pièces d'identité, sécurité).
    En lecture seule : une ligne ne se modifie ni ne se supprime depuis l'admin.
    """

    actor = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+"
    )
    action = models.CharField(max_length=60, db_index=True)
    target = models.CharField("objet concerné", max_length=200, blank=True)
    data = models.JSONField(default=dict, blank=True)
    ip = models.GenericIPAddressField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True, db_index=True)

    class Meta:
        verbose_name = "journal d'audit"
        verbose_name_plural = "journal d'audit"
        ordering = ["-created_at"]

    def __str__(self):
        return f"{self.created_at:%d/%m/%Y %H:%M} {self.action} {self.target}"
