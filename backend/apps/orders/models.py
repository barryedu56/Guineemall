from django.conf import settings
from django.db import models

from apps.core.models import SoftDeleteModel, TimeStampedModel
from apps.core.phone import PhoneField


class CartItem(TimeStampedModel):
    """Panier : rattaché à un utilisateur connecté ou à une session invitée."""

    user = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.CASCADE, null=True, blank=True, related_name="cart_items"
    )
    session_key = models.CharField(max_length=64, blank=True, db_index=True)
    product = models.ForeignKey("catalog.Product", on_delete=models.CASCADE, related_name="+")
    quantity = models.PositiveIntegerField(default=1)

    class Meta:
        verbose_name = "article du panier"
        constraints = [
            models.UniqueConstraint(
                fields=["user", "product"], condition=models.Q(user__isnull=False), name="unique_cart_user_product"
            ),
            models.UniqueConstraint(
                fields=["session_key", "product"],
                condition=models.Q(user__isnull=True),
                name="unique_cart_session_product",
            ),
            models.CheckConstraint(
                condition=models.Q(user__isnull=False) | ~models.Q(session_key=""),
                name="cart_has_owner",
            ),
        ]

    @property
    def subtotal(self):
        return self.product.price * self.quantity


class Order(TimeStampedModel, SoftDeleteModel):
    """Commande globale du client. Elle est découpée en VendorOrder (une par boutique)."""

    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        PROCESSING = "processing", "En cours"
        DELIVERED = "delivered", "Livrée"
        CANCELLED = "cancelled", "Annulée"

    class PaymentMethod(models.TextChoices):
        CASH_ON_DELIVERY = "cash_on_delivery", "Paiement à la livraison"
        ORANGE_MONEY = "orange_money", "Orange Money"
        MTN_MOMO = "mtn_momo", "MTN Mobile Money"
        CARD = "card", "Carte bancaire"
        STORE_CREDIT = "store_credit", "Avoir GuinéeMall"

    class PaymentStatus(models.TextChoices):
        PENDING = "pending", "En attente"
        PAID = "paid", "Payé"
        FAILED = "failed", "Échoué"
        REFUND_PENDING = "refund_pending", "Remboursement à effectuer"
        REFUNDED = "refunded", "Remboursé"

    class DeliveryMethod(models.TextChoices):
        HOME = "home", "Livraison à domicile"
        PICKUP = "pickup", "Retrait en point relais"

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.PROTECT, related_name="orders")
    subtotal = models.PositiveBigIntegerField("sous-total articles (GNF)", default=0)
    delivery_fee = models.PositiveBigIntegerField("frais de livraison (GNF)", default=0)
    discount_amount = models.PositiveBigIntegerField("réduction (GNF)", default=0)
    total_amount = models.PositiveBigIntegerField("total (GNF)")
    credit_used = models.PositiveBigIntegerField("payé avec l'avoir (GNF)", default=0)
    refunded_amount = models.PositiveBigIntegerField("remboursé en avoir (GNF)", default=0)
    coupon = models.ForeignKey("promotions.Coupon", on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    affiliate = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="affiliated_orders",
        help_text="Ambassadeur dont le lien a amené le client",
    )
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.PENDING)

    payment_method = models.CharField(
        max_length=30, choices=PaymentMethod.choices, default=PaymentMethod.CASH_ON_DELIVERY
    )
    payment_reference = models.CharField(max_length=100, blank=True)
    payment_status = models.CharField(max_length=20, choices=PaymentStatus.choices, default=PaymentStatus.PENDING)

    delivery_method = models.CharField(max_length=10, choices=DeliveryMethod.choices, default=DeliveryMethod.HOME)
    pickup_point = models.ForeignKey(
        "delivery.PickupPoint", on_delete=models.SET_NULL, null=True, blank=True, related_name="orders"
    )
    # Copie de l'adresse au moment de la commande : elle ne doit pas changer
    # si le client modifie ou supprime son adresse plus tard.
    delivery_name = models.CharField(
        "destinataire", max_length=150, blank=True, help_text="Si quelqu'un d'autre reçoit le colis (achat diaspora)"
    )
    delivery_city = models.CharField(max_length=100)
    delivery_commune = models.CharField(max_length=100, blank=True)
    delivery_quartier = models.CharField(max_length=100, blank=True)
    delivery_landmark = models.TextField()
    delivery_latitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    delivery_longitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    delivery_phone = PhoneField("téléphone de livraison")

    class Meta:
        verbose_name = "commande"
        ordering = ["-created_at"]
        indexes = [models.Index(fields=["status"]), models.Index(fields=["user", "-created_at"])]

    def __str__(self):
        return f"Commande #{self.pk}"

    @property
    def amount_due(self):
        """Ce qu'il reste à payer en ligne une fois l'avoir déduit."""
        return self.total_amount - self.credit_used


class VendorOrder(TimeStampedModel):
    """Partie d'une commande qui concerne un seul vendeur."""

    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        CONFIRMED = "confirmed", "Confirmée"
        PREPARING = "preparing", "En préparation"
        READY = "ready", "Prête pour livraison"
        DELIVERED = "delivered", "Livrée"
        CANCELLED = "cancelled", "Annulée"

    order = models.ForeignKey(Order, on_delete=models.CASCADE, related_name="vendor_orders")
    vendor = models.ForeignKey("vendors.Vendor", on_delete=models.PROTECT, related_name="vendor_orders")
    subtotal = models.PositiveBigIntegerField("sous-total articles (GNF)", default=0)
    delivery_fee = models.PositiveBigIntegerField("frais de livraison (GNF)", default=0)
    vendor_discount = models.PositiveBigIntegerField("réduction payée par la boutique (GNF)", default=0)
    platform_discount = models.PositiveBigIntegerField("réduction payée par GuinéeMall (GNF)", default=0)
    total_amount = models.PositiveBigIntegerField("total payé par le client (GNF)")
    status = models.CharField(max_length=20, choices=Status.choices, default=Status.PENDING)

    # Code secret donné au client quand le colis est prêt : sans lui, impossible de marquer « livrée »
    delivery_code = models.CharField("code de livraison", max_length=4, blank=True)
    delivery_code_attempts = models.PositiveSmallIntegerField(
        "codes faux saisis", default=0, help_text="Remettre à 0 pour débloquer après vérification"
    )

    # Livraison bloquée (codes faux) : la version du client, qui seul sait s'il a le colis
    class ClientReport(models.TextChoices):
        RECEIVED = "received", "Le client confirme avoir reçu le colis"
        NOT_RECEIVED = "not_received", "Le client dit ne rien avoir reçu"

    client_report = models.CharField(max_length=15, choices=ClientReport.choices, blank=True)
    client_report_at = models.DateTimeField(null=True, blank=True)
    confirmation_requested_at = models.DateTimeField("confirmation demandée au client le", null=True, blank=True)
    contest_until = models.DateTimeField(
        "contestable jusqu'au", null=True, blank=True,
        help_text="Réception validée par l'équipe sans réponse du client : il peut la contester jusqu'à cette date",
    )

    accepted_at = models.DateTimeField("acceptée le", null=True, blank=True)
    ready_at = models.DateTimeField("colis prêt le", null=True, blank=True)

    # Séquestre : l'argent est versé au vendeur après confirmation de réception
    delivered_at = models.DateTimeField(null=True, blank=True)
    received_at = models.DateTimeField("réception confirmée le", null=True, blank=True)
    funds_released_at = models.DateTimeField("versé au vendeur le", null=True, blank=True)

    class Meta:
        verbose_name = "commande vendeur"
        ordering = ["-created_at"]
        indexes = [models.Index(fields=["status"]), models.Index(fields=["vendor", "status"])]
        constraints = [
            models.UniqueConstraint(fields=["order", "vendor"], name="unique_vendor_order"),
        ]

    def __str__(self):
        return f"Commande #{self.order_id} – {self.vendor}"


class OrderItem(models.Model):
    order = models.ForeignKey(Order, on_delete=models.CASCADE, related_name="items")
    vendor_order = models.ForeignKey(VendorOrder, on_delete=models.CASCADE, related_name="items")
    product = models.ForeignKey("catalog.Product", on_delete=models.SET_NULL, null=True, related_name="+")
    # Copie du nom et du prix : l'historique reste juste même si le produit change.
    product_name = models.CharField(max_length=255)
    unit_price = models.PositiveBigIntegerField("prix unitaire (GNF)")
    quantity = models.PositiveIntegerField()

    class Meta:
        verbose_name = "article commandé"

    @property
    def subtotal(self):
        return self.unit_price * self.quantity


class DeliveryCodeAttempt(models.Model):
    """Chaque code de livraison saisi (bon ou faux) : qui, quand. Sert à l'équipe en cas de blocage."""

    vendor_order = models.ForeignKey(VendorOrder, on_delete=models.CASCADE, related_name="code_attempts")
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    success = models.BooleanField(default=False)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "essai de code de livraison"
        ordering = ["created_at"]


class OrderStatusHistory(models.Model):
    order = models.ForeignKey(Order, on_delete=models.CASCADE, related_name="status_history")
    status = models.CharField(max_length=30)
    changed_by = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+"
    )
    note = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "historique de statut"
        ordering = ["created_at"]
