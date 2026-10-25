from django.conf import settings
from django.db import models

from apps.core.formatting import gnf
from apps.core.models import TimeStampedModel
from apps.core.phone import PhoneField


class DeliveryZone(models.Model):
    """Tarif de livraison par ville et commune (commune vide = toute la ville)."""

    city = models.CharField("ville", max_length=100)
    commune = models.CharField(max_length=100, blank=True)
    fee = models.PositiveIntegerField("frais (GNF)")
    delay = models.CharField("délai indicatif", max_length=50, default="24 à 48 h")
    is_active = models.BooleanField(default=True)

    class Meta:
        verbose_name = "zone de livraison"
        ordering = ["city", "commune"]
        constraints = [models.UniqueConstraint(fields=["city", "commune"], name="unique_delivery_zone")]

    def __str__(self):
        return f"{self.commune or 'Toute la ville'}, {self.city} : {gnf(self.fee)}"

    @classmethod
    def find(cls, city, commune=""):
        """Tarif le plus précis : la commune si elle existe, sinon la ville."""
        zones = cls.objects.filter(is_active=True, city__iexact=city)
        if commune:
            zone = zones.filter(commune__iexact=commune).first()
            if zone:
                return zone
        return zones.filter(commune="").first()


class PickupPoint(models.Model):
    """Point relais : boutique partenaire où le client récupère son colis, moins cher que la livraison."""

    name = models.CharField("nom", max_length=150)
    city = models.CharField("ville", max_length=100)
    commune = models.CharField(max_length=100, blank=True)
    address = models.TextField("adresse / repère")
    phone = PhoneField("téléphone")
    opening_hours = models.CharField("horaires", max_length=100, default="Lun-Sam, 8 h - 19 h")
    fee = models.PositiveIntegerField("frais (GNF)", default=0)
    latitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    longitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    is_active = models.BooleanField(default=True)

    class Meta:
        verbose_name = "point relais"
        ordering = ["city", "name"]

    def __str__(self):
        return f"{self.name} ({self.commune or self.city})"


class Rider(TimeStampedModel):
    """Livreur à moto."""

    user = models.OneToOneField(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="rider")
    city = models.CharField("ville", max_length=100, default="Conakry")
    communes = models.CharField("communes desservies", max_length=255, blank=True, help_text="Séparées par des virgules")
    vehicle_plate = models.CharField("plaque", max_length=20, blank=True)
    is_approved = models.BooleanField("approuvé", default=False)
    is_available = models.BooleanField("disponible", default=True)
    latitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    longitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    location_updated_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "livreur"

    @property
    def communes_list(self):
        return [c.strip() for c in self.communes.split(",") if c.strip()]

    def __str__(self):
        return self.user.name


class Delivery(TimeStampedModel):
    """
    Course de livraison d'une sous-commande (une boutique → un client).
    La boutique choisit le livreur : la course lui est proposée, il accepte ou refuse.
    Aucun livreur ne voit une course qui ne lui a pas été proposée.
    """

    class Status(models.TextChoices):
        OPEN = "open", "Livreur à choisir"
        OFFERED = "offered", "Proposée au livreur"
        ASSIGNED = "assigned", "Livreur en route vers la boutique"
        PICKED_UP = "picked_up", "Colis récupéré, en route"
        DELIVERED = "delivered", "Livré"
        CANCELLED = "cancelled", "Annulée"

    vendor_order = models.OneToOneField("orders.VendorOrder", on_delete=models.CASCADE, related_name="delivery")
    rider = models.ForeignKey(Rider, on_delete=models.SET_NULL, null=True, blank=True, related_name="deliveries")
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.OPEN)
    fee = models.PositiveIntegerField("rémunération livreur (GNF)", default=0)
    offered_at = models.DateTimeField("proposée le", null=True, blank=True)
    accepted_at = models.DateTimeField("acceptée le", null=True, blank=True)
    declined_by = models.ManyToManyField(Rider, blank=True, related_name="declined_deliveries", verbose_name="refusée par")
    picked_up_at = models.DateTimeField(null=True, blank=True)
    delivered_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "livraison"
        verbose_name_plural = "livraisons"
        ordering = ["-created_at"]
        indexes = [models.Index(fields=["status"])]


class DeliveryMessage(models.Model):
    """Discussion boutique ↔ livreur à propos d'une course (un fil par livreur)."""

    delivery = models.ForeignKey(Delivery, on_delete=models.CASCADE, related_name="messages")
    rider = models.ForeignKey(Rider, on_delete=models.CASCADE, related_name="+")
    sender = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="+")
    content = models.TextField(blank=True)
    image = models.ImageField(upload_to="delivery_chat/", blank=True)
    audio = models.FileField("message vocal", upload_to="delivery_chat/", blank=True)
    read_at = models.DateTimeField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "message de course"
        ordering = ["created_at"]
        indexes = [models.Index(fields=["delivery", "rider", "created_at"])]


class DeliveryRating(models.Model):
    """Note laissée par le client au livreur après la livraison."""

    delivery = models.OneToOneField(Delivery, on_delete=models.CASCADE, related_name="rating")
    rating = models.PositiveSmallIntegerField("note", choices=[(i, f"{i} ★") for i in range(1, 6)])
    comment = models.CharField("commentaire", max_length=300, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "note de livraison"
