from django.db import transaction
from django.utils import timezone

from apps.notifications.services import notify, push_to_user

from .models import Message


def recipient_of(conversation, sender):
    return conversation.vendor.user if sender.id == conversation.client_id else conversation.client


def send_message(conversation, sender, serializer):
    message = serializer.save(conversation=conversation, sender=sender)
    conversation.last_message_at = message.created_at
    conversation.save(update_fields=["last_message_at", "updated_at"])

    recipient = recipient_of(conversation, sender)
    data = serializer.__class__(message, context=serializer.context).data
    event = {"type": "chat.message", "conversation": conversation.id, "message": data}

    def broadcast():
        push_to_user(recipient.id, event)
        push_to_user(sender.id, event)  # autres onglets / appareils de l'expéditeur

    transaction.on_commit(broadcast)

    # Notification (sans WhatsApp, pour ne pas inonder) au premier message non lu
    unread = conversation.messages.filter(read_at__isnull=True).exclude(sender=recipient).count()
    if unread == 1:
        name = conversation.vendor.shop_name if sender.id != conversation.client_id else sender.name
        link = "/vendeur/messages" if recipient.is_vendeur else "/messages"
        notify(recipient, "message", f"Nouveau message de {name}", message.content[:120], f"{link}/{conversation.id}",
               whatsapp=False)
    return message


def mark_read(conversation, reader):
    updated = conversation.messages.filter(read_at__isnull=True).exclude(sender=reader).update(read_at=timezone.now())
    if updated:
        other = recipient_of(conversation, reader)
        push_to_user(other.id, {"type": "chat.read", "conversation": conversation.id})
    return updated
