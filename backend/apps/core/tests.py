from asgiref.sync import async_to_sync
from channels.testing import WebsocketCommunicator
from django.test import TransactionTestCase
from rest_framework_simplejwt.tokens import AccessToken

from apps.accounts.models import User
from apps.notifications.services import push_to_user
from config.asgi import application


class WebSocketTests(TransactionTestCase):
    """Connexion temps réel réelle (Redis) : authentification par jeton et réception d'évènements."""

    def test_refus_sans_jeton(self):
        async def scenario():
            ws = WebsocketCommunicator(application, "/ws/", headers=[(b"origin", b"http://localhost")])
            connected, _ = await ws.connect()
            await ws.disconnect()
            return connected

        self.assertFalse(async_to_sync(scenario)())

    def test_reception_d_un_evenement(self):
        user = User.objects.create_user(email="ws@test.gn", password="x", name="WS")
        token = str(AccessToken.for_user(user))

        async def scenario():
            ws = WebsocketCommunicator(application, f"/ws/?token={token}", headers=[(b"origin", b"http://localhost")])
            connected, _ = await ws.connect()
            assert connected
            await ws.send_json_to({"type": "ping"})
            pong = await ws.receive_json_from(timeout=3)
            from asgiref.sync import sync_to_async

            await sync_to_async(push_to_user)(user.id, {"type": "chat.message", "conversation": 1})
            event = await ws.receive_json_from(timeout=3)
            await ws.disconnect()
            return pong, event

        pong, event = async_to_sync(scenario)()
        self.assertEqual(pong, {"type": "pong"})
        self.assertEqual(event, {"type": "chat.message", "conversation": 1})
