"""
Point d'entrée ASGI : HTTP (Django) + WebSocket (Channels).
Lancer en production avec : daphne config.asgi:application
"""

import os

from django.core.asgi import get_asgi_application

os.environ.setdefault("DJANGO_SETTINGS_MODULE", "config.settings")
django_asgi_app = get_asgi_application()

from channels.routing import ProtocolTypeRouter, URLRouter  # noqa: E402
from channels.security.websocket import AllowedHostsOriginValidator  # noqa: E402
from django.urls import path  # noqa: E402

from apps.core.websocket import JWTAuthMiddleware, UserConsumer  # noqa: E402
from apps.lives.consumers import LiveConsumer  # noqa: E402

application = ProtocolTypeRouter({
    "http": django_asgi_app,
    "websocket": AllowedHostsOriginValidator(
        JWTAuthMiddleware(
            URLRouter([
                path("ws/", UserConsumer.as_asgi()),
                path("ws/live/<int:live_id>/", LiveConsumer.as_asgi()),
            ])
        )
    ),
})
