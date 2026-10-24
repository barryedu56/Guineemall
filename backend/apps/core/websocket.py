"""
Connexion WebSocket unique par utilisateur : /ws/?token=<jeton d'accès JWT>

Le serveur y envoie les évènements temps réel destinés à l'utilisateur :
nouveaux messages, « en train d'écrire », notifications, position du livreur.
Les directs (lives) utilisent /ws/live/<id>/ pour leurs commentaires.
"""

from urllib.parse import parse_qs

from channels.db import database_sync_to_async
from channels.generic.websocket import AsyncJsonWebsocketConsumer
from django.contrib.auth.models import AnonymousUser
from rest_framework_simplejwt.authentication import JWTAuthentication
from rest_framework_simplejwt.exceptions import InvalidToken, TokenError


@database_sync_to_async
def user_from_token(token):
    auth = JWTAuthentication()
    try:
        return auth.get_user(auth.get_validated_token(token))
    except (InvalidToken, TokenError, Exception):
        return AnonymousUser()


class JWTAuthMiddleware:
    def __init__(self, app):
        self.app = app

    async def __call__(self, scope, receive, send):
        token = parse_qs(scope.get("query_string", b"").decode()).get("token", [None])[0]
        scope["user"] = await user_from_token(token) if token else AnonymousUser()
        return await self.app(scope, receive, send)


class UserConsumer(AsyncJsonWebsocketConsumer):
    async def connect(self):
        user = self.scope["user"]
        if not user.is_authenticated:
            await self.close(code=4401)
            return
        self.group = f"user_{user.id}"
        await self.channel_layer.group_add(self.group, self.channel_name)
        await self.accept()

    async def disconnect(self, code):
        if hasattr(self, "group"):
            await self.channel_layer.group_discard(self.group, self.channel_name)

    async def receive_json(self, content, **kwargs):
        if content.get("type") == "ping":
            await self.send_json({"type": "pong"})

    async def push(self, event):
        await self.send_json(event["event"])
