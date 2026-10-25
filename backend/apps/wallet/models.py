from django.conf import settings
from django.db import models
from django.db.models import Sum

from apps.core.formatting import gnf
from apps.core.models import TimeStampedModel
from apps.core.phone import PhoneField


class LedgerEntry(models.Model):
    """
    Mouvement du portefeuille d'un utilisateur (vendeur, ambassadeur, livreur).
    Le solde est la somme des montants ; un montant négatif est un débit.
    """

    class Kind(models.TextChoices):
        SALE = "sale", "Vente (après commission)"
        COD_COMMISSION = "cod_commission", "Commission sur vente payée à la livraison"
        AFFILIATE = "affiliate", "Commission ambassadeur"
        DELIVERY = "delivery", "Course de livraison"
        PAYOUT = "payout", "Retrait"
        PAYOUT_CANCELLED = "payout_cancelled", "Retrait refusé (remboursé)"
        CASH_DEPOSIT = "cash_deposit", "Espèces reversées à la plateforme"
        ADJUSTMENT = "adjustment", "Ajustement"

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.PROTECT, related_name="ledger_entries")
    kind = models.CharField(max_length=20, choices=Kind.choices)
    amount = models.BigIntegerField("montant (GNF)")
    vendor_order = models.ForeignKey(
        "orders.VendorOrder", on_delete=models.SET_NULL, null=True, blank=True, related_name="ledger_entries"
    )
    payout = models.ForeignKey("PayoutRequest", on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    deposit = models.ForeignKey("CashDeposit", on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    note = models.CharField(max_length=255, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "mouvement"
        ordering = ["-created_at"]
        constraints = [
            # Empêche de créditer deux fois la même vente au même bénéficiaire
            models.UniqueConstraint(
                fields=["user", "vendor_order", "kind"],
                condition=models.Q(vendor_order__isnull=False),
                name="unique_ledger_per_vendor_order",
            ),
        ]

    @staticmethod
    def balance(user):
        return LedgerEntry.objects.filter(user=user).aggregate(total=Sum("amount"))["total"] or 0


class PayoutRequest(TimeStampedModel):
    """Demande de retrait vers Orange Money / MTN, traitée par un admin."""

    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        SENDING = "sending", "Envoi en cours"
        PAID = "paid", "Payé"
        REJECTED = "rejected", "Refusé"

    class Method(models.TextChoices):
        ORANGE_MONEY = "orange_money", "Orange Money"
        MTN_MOMO = "mtn_momo", "MTN Mobile Money"

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.PROTECT, related_name="payouts")
    amount = models.PositiveBigIntegerField("montant (GNF)")
    method = models.CharField(max_length=20, choices=Method.choices)
    phone = PhoneField("numéro")
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    transaction_reference = models.CharField("référence du transfert", max_length=100, blank=True)
    provider_reference = models.CharField("référence de l'envoi automatique", max_length=100, blank=True)
    admin_note = models.CharField(max_length=255, blank=True)
    processed_by = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+"
    )
    processed_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "demande de retrait"
        ordering = ["-created_at"]

    def __str__(self):
        return f"Retrait {gnf(self.amount)} – {self.user}"


class CashDeposit(TimeStampedModel):
    """
    Argent reversé à la plateforme par un livreur (espèces encaissées chez les clients)
    ou une boutique (commissions des ventes payées à la livraison). Confirmé par un admin.
    """

    class Status(models.TextChoices):
        PENDING = "pending", "À vérifier"
        CONFIRMED = "confirmed", "Confirmé"
        REJECTED = "rejected", "Refusé"

    class Method(models.TextChoices):
        ORANGE_MONEY = "orange_money", "Orange Money"
        MTN_MOMO = "mtn_momo", "MTN Mobile Money"
        OFFICE = "office", "Dépôt en espèces au bureau"

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.PROTECT, related_name="cash_deposits")
    amount = models.PositiveBigIntegerField("montant (GNF)")
    method = models.CharField(max_length=20, choices=Method.choices)
    reference = models.CharField("référence du transfert / reçu", max_length=100)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    admin_note = models.CharField(max_length=255, blank=True)
    processed_by = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+"
    )
    processed_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "versement d'espèces"
        verbose_name_plural = "versements d'espèces"
        ordering = ["-created_at"]

    def __str__(self):
        return f"Versement {gnf(self.amount)} – {self.user}"


class CreditEntry(models.Model):
    """
    Avoir du client : uniquement alimenté par des remboursements (jamais rechargé par le client,
    ce n'est donc pas de la monnaie électronique). Le solde est la somme des montants.
    """

    class Kind(models.TextChoices):
        REFUND = "refund", "Remboursement d'une commande"
        USED = "used", "Utilisé pour une commande"
        RESTORED = "restored", "Rendu (commande non payée)"
        CASH_OUT = "cash_out", "Remboursé sur Mobile Money"
        CASH_OUT_CANCELLED = "cash_out_cancelled", "Remboursement Mobile Money refusé"
        ADJUSTMENT = "adjustment", "Geste commercial / ajustement"

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.PROTECT, related_name="credit_entries")
    kind = models.CharField(max_length=20, choices=Kind.choices)
    amount = models.BigIntegerField("montant (GNF)")
    order = models.ForeignKey("orders.Order", on_delete=models.SET_NULL, null=True, blank=True, related_name="credit_entries")
    refund = models.ForeignKey("CreditRefund", on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    note = models.CharField(max_length=255, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "mouvement d'avoir"
        verbose_name_plural = "mouvements d'avoir"
        ordering = ["-created_at"]

    @staticmethod
    def balance(user):
        return CreditEntry.objects.filter(user=user).aggregate(total=Sum("amount"))["total"] or 0


class CreditRefund(TimeStampedModel):
    """Le client demande que son avoir lui soit rendu sur Orange Money / MTN ; un admin envoie l'argent."""

    class Status(models.TextChoices):
        PENDING = "pending", "En attente"
        SENDING = "sending", "Envoi en cours"
        PAID = "paid", "Remboursé"
        REJECTED = "rejected", "Refusé"

    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.PROTECT, related_name="credit_refunds")
    amount = models.PositiveBigIntegerField("montant (GNF)")
    method = models.CharField(max_length=20, choices=PayoutRequest.Method.choices)
    phone = PhoneField("numéro")
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    transaction_reference = models.CharField("référence du transfert", max_length=100, blank=True)
    provider_reference = models.CharField("référence de l'envoi automatique", max_length=100, blank=True)
    admin_note = models.CharField(max_length=255, blank=True)
    processed_by = models.ForeignKey(
        settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+"
    )
    processed_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "remboursement d'avoir"
        verbose_name_plural = "remboursements d'avoir"
        ordering = ["-created_at"]

    def __str__(self):
        return f"Remboursement {gnf(self.amount)} – {self.user}"


class PayoutAccount(TimeStampedModel):
    """
    Numéro qui reçoit les retraits d'un utilisateur. Vérifié une fois par l'équipe (nom du titulaire) ;
    le changer demande le mot de passe, repasse en vérification et bloque les retraits PAYOUT_ACCOUNT_HOLD_HOURS.
    Protège contre le vol d'un compte : l'argent ne peut pas partir vers un autre numéro.
    """

    class Status(models.TextChoices):
        PENDING = "pending", "En cours de vérification"
        VERIFIED = "verified", "Vérifié"
        REJECTED = "rejected", "Refusé"

    user = models.OneToOneField(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="payout_account")
    method = models.CharField(max_length=20, choices=PayoutRequest.Method.choices)
    phone = PhoneField("numéro")
    holder_name = models.CharField("nom du titulaire", max_length=150)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDING)
    admin_note = models.CharField(max_length=255, blank=True)
    hold_until = models.DateTimeField("retraits bloqués jusqu'au", null=True, blank=True)
    verified_by = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    verified_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "compte de retrait"
        verbose_name_plural = "comptes de retrait"
        ordering = ["status", "-updated_at"]

    def __str__(self):
        return f"{self.get_method_display()} {self.phone} ({self.holder_name})"
