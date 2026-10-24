from django.conf import settings
from django.db import models

from apps.core.models import SoftDeleteModel, TimeStampedModel, unique_slug


class Category(TimeStampedModel):
    class Status(models.TextChoices):
        ACTIVE = "active", "Active"
        INACTIVE = "inactive", "Inactive"

    name = models.CharField("nom", max_length=255)
    slug = models.SlugField(max_length=255, unique=True, blank=True)
    description = models.TextField(blank=True)
    parent = models.ForeignKey(
        "self", on_delete=models.SET_NULL, null=True, blank=True, related_name="children"
    )
    image = models.ImageField(upload_to="categories/", blank=True)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.ACTIVE)

    class Meta:
        verbose_name = "catégorie"
        ordering = ["name"]

    def __str__(self):
        return self.name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = unique_slug(self, self.name)
        super().save(*args, **kwargs)


class Product(TimeStampedModel, SoftDeleteModel):
    class Status(models.TextChoices):
        ACTIVE = "active", "Actif"
        INACTIVE = "inactive", "Inactif"

    vendor = models.ForeignKey("vendors.Vendor", on_delete=models.CASCADE, related_name="products")
    category = models.ForeignKey(Category, on_delete=models.PROTECT, related_name="products")
    name = models.CharField("nom", max_length=255)
    slug = models.SlugField(max_length=255, unique=True, blank=True)
    description = models.TextField()
    # Prix en francs guinéens (GNF) : pas de centimes, mais des montants élevés.
    # L'ancien decimal(10,2) plafonnait à ~100 millions GNF.
    price = models.PositiveBigIntegerField("prix (GNF)")
    compare_at_price = models.PositiveBigIntegerField(
        "ancien prix (GNF)", null=True, blank=True, help_text="Prix barré pour les promotions"
    )
    stock = models.PositiveIntegerField(default=0)
    # Attributs libres selon la catégorie : {"taille": "42", "couleur": "noir"}
    attributes = models.JSONField(default=dict, blank=True)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.ACTIVE)

    class Meta:
        verbose_name = "produit"
        ordering = ["-created_at"]
        indexes = [models.Index(fields=["status"]), models.Index(fields=["vendor", "status"])]

    def __str__(self):
        return self.name

    def save(self, *args, **kwargs):
        if not self.slug:
            self.slug = unique_slug(self, self.name)
        super().save(*args, **kwargs)

    @property
    def in_stock(self):
        return self.stock > 0

    @property
    def main_image(self):
        return self.images.first()


class ProductImage(models.Model):
    """Plusieurs photos par produit (l'ancien projet n'en gérait qu'une)."""

    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name="images")
    image = models.ImageField(upload_to="products/")
    position = models.PositiveSmallIntegerField(default=0)

    class Meta:
        verbose_name = "image produit"
        ordering = ["position", "id"]


class Favorite(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="favorites")
    product = models.ForeignKey(Product, on_delete=models.CASCADE, related_name="favorited_by")
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "favori"
        constraints = [
            models.UniqueConstraint(fields=["user", "product"], name="unique_favorite"),
        ]
