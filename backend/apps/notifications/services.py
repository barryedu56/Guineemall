from asgiref.sync import async_to_sync
from channels.layers import get_channel_layer
from django.conf import settings
from django.db import transaction

from .models import Notification


def push_to_user(user_id, event):
    """Envoie un évènement temps réel à toutes les connexions WebSocket de l'utilisateur."""
    layer = get_channel_layer()
    if layer is None:
        return
    try:
        async_to_sync(layer.group_send)(f"user_{user_id}", {"type": "push", "event": event})
    except Exception:  # Redis indisponible : la notification reste visible dans l'application
        pass


def notify(user, kind, title, body="", link="", whatsapp=True):
    """
    Notifie un utilisateur : notification dans le site (temps réel) + WhatsApp.
    L'envoi a lieu après la validation de la transaction en cours.
    """
    notification = Notification.objects.create(user=user, kind=kind, title=title, body=body, link=link)

    def deliver():
        push_to_user(user.id, {
            "type": "notification",
            "notification": {
                "id": notification.id, "kind": kind, "title": title, "body": body,
                "link": link, "created_at": notification.created_at.isoformat(),
            },
        })
        if whatsapp and user.phone:
            from .tasks import send_whatsapp_task

            text = f"{body}\n{settings.FRONTEND_URL}{link}" if link else body
            send_whatsapp_task.delay(user.phone, title, text)

    transaction.on_commit(deliver)
    return notification
