from django.conf import settings
from django.db import models

from apps.core.models import TimeStampedModel


class Conversation(TimeStampedModel):
    """Discussion entre un client et une boutique (une seule par couple)."""

    client = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="conversations")
    vendor = models.ForeignKey("vendors.Vendor", on_delete=models.CASCADE, related_name="conversations")
    last_message_at = models.DateTimeField(null=True, blank=True)

    class Meta:
        verbose_name = "conversation"
        ordering = ["-last_message_at", "-created_at"]
        constraints = [
            models.UniqueConstraint(fields=["client", "vendor"], name="unique_conversation"),
        ]

    def __str__(self):
        return f"{self.client} ↔ {self.vendor}"

    def has_participant(self, user):
        return user.pk == self.client_id or user.pk == self.vendor.user_id


class Message(models.Model):
    class Type(models.TextChoices):
        TEXT = "text", "Texte"
        IMAGE = "image", "Image"
        FILE = "file", "Fichier"
        VOICE = "voice", "Message vocal"

    conversation = models.ForeignKey(Conversation, on_delete=models.CASCADE, related_name="messages")
    sender = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="sent_messages")
    type = models.CharField(max_length=10, choices=Type.choices, default=Type.TEXT)
    content = models.TextField(blank=True)
    attachment = models.FileField(upload_to="chat/", blank=True)
    # Contexte : le client écrit à propos d'un produit ou d'une commande
    product = models.ForeignKey("catalog.Product", on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    order = models.ForeignKey("orders.Order", on_delete=models.SET_NULL, null=True, blank=True, related_name="+")
    read_at = models.DateTimeField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "message"
        ordering = ["created_at"]
        indexes = [models.Index(fields=["conversation", "created_at"])]

    @property
    def is_read(self):
        return self.read_at is not None
