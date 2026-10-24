from channels.db import database_sync_to_async
from channels.generic.websocket import AsyncJsonWebsocketConsumer

from .models import LiveComment, LiveSession


class LiveConsumer(AsyncJsonWebsocketConsumer):
    """Commentaires en direct d'un live : tout le monde lit, les connectés écrivent."""

    async def connect(self):
        self.live_id = self.scope["url_route"]["kwargs"]["live_id"]
        if not await self.live_exists():
            await self.close()
            return
        self.group = f"live_{self.live_id}"
        await self.channel_layer.group_add(self.group, self.channel_name)
        await self.accept()

    async def disconnect(self, code):
        if hasattr(self, "group"):
            await self.channel_layer.group_discard(self.group, self.channel_name)

    async def receive_json(self, content, **kwargs):
        user = self.scope["user"]
        text = str(content.get("text", "")).strip()[:300]
        if content.get("type") != "comment" or not text or not user.is_authenticated:
            return
        comment = await self.save_comment(user, text)
        await self.channel_layer.group_send(self.group, {"type": "push", "event": {
            "type": "live.comment",
            "comment": {"id": comment.id, "author": user.name.split()[0], "text": text,
                        "created_at": comment.created_at.isoformat()},
        }})

    async def push(self, event):
        await self.send_json(event["event"])

    @database_sync_to_async
    def live_exists(self):
        return LiveSession.objects.filter(pk=self.live_id).exclude(status=LiveSession.Status.ENDED).exists()

    @database_sync_to_async
    def save_comment(self, user, text):
        return LiveComment.objects.create(live_id=self.live_id, user=user, text=text)
