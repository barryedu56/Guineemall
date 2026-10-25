from django.conf import settings
from django.db import models
from django.utils import timezone

from apps.core.models import TimeStampedModel, unique_slug
from apps.core.uploads import validate_image_5mb


class Coupon(TimeStampedModel):
    """
    Code promo. Sans boutique : offert par GuinéeMall (la plateforme paie la réduction).
    Avec boutique : offert par la boutique, ne s'applique qu'à ses articles.
    Avec utilisateur : bon personnel (parrainage), utilisable par lui seul.
    """

    class Type(models.TextChoices):
        PERCENT = "percent", "Pourcentage"
        FIXED = "fixed", "Montant fixe"

    code = models.CharField(max_length=30, unique=True)
    type = models.CharField(max_length=10, choices=Type.choices, default=Type.PERCENT)
    value = models.PositiveIntegerField("valeur", help_text="% ou montant en GNF")
    max_discount = models.PositiveIntegerField("réduction maximale (GNF)", null=True, blank=True)
    min_amount = models.PositiveIntegerField("montant minimum (GNF)", default=0)
    vendor = models.ForeignKey("vendors.Vendor", on_delete=models.CASCADE, null=True, blank=True, related_name="coupons")
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.CASCADE, null=True, blank=True, related_name="personal_coupons"
    )
    starts_at = models.DateTimeField(default=timezone.now)
    ends_at = models.DateTimeField(null=True, blank=True)
    usage_limit = models.PositiveIntegerField("utilisations max", null=True, blank=True)
    per_user_limit = models.PositiveIntegerField("utilisations max par client", default=1)
    used_count = models.PositiveIntegerField(default=0, editable=False)
    is_active = models.BooleanField(default=True)
    description = models.CharField(max_length=200, blank=True)

    class Meta:
        verbose_name = "code promo"

    def __str__(self):
        return self.code

    def save(self, *args, **kwargs):
        self.code = self.code.upper().strip()
        super().save(*args, **kwargs)

    def compute_discount(self, amount):
        if self.type == self.Type.PERCENT:
            discount = amount * min(self.value, 100) // 100
        else:
            discount = self.value
        if self.max_discount:
            discount = min(discount, self.max_discount)
        return min(discount, amount)


class CouponRedemption(models.Model):
    coupon = models.ForeignKey(Coupon, on_delete=models.CASCADE, related_name="redemptions")
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="+")
    order = models.ForeignKey("orders.Order", on_delete=models.CASCADE, related_name="+")
    amount = models.PositiveIntegerField()
    created_at = models.DateTimeField(auto_now_add=True)


class Campaign(TimeStampedModel):
    """Opération commerciale : Tabaski, Ramadan, rentrée scolaire, ventes flash…"""

    name = models.CharField("nom", max_length=150)
    slug = models.SlugField(max_length=160, unique=True, blank=True)
    description = models.TextField(blank=True)
    banner = models.ImageField(upload_to="campaigns/", blank=True, validators=[validate_image_5mb])
    color = models.CharField("couleur", max_length=7, default="#FFC107")
    starts_at = models.DateTimeField()
    ends_at = models.DateTimeField()
    products = models.ManyToManyField("catalog.Product", blank=True, related_name="campaigns")
    coupon = models.ForeignKey(Coupon, on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    is_active = models.BooleanField(default=True)

    class Meta:
        verbose_name = "opération commerciale"
        ordering = ["-starts_at"]

    def __str__(self):
        return self.name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = unique_slug(self, self.name)
        super().save(*args, **kwargs)

    @property
    def is_running(self):
        return self.is_active and self.starts_at <= timezone.now() <= self.ends_at
