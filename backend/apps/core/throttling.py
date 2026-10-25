"""
Limitation des tentatives (anti force brute, anti robots, anti abus du parrainage).
Les compteurs sont gardés dans Redis. Au-delà de la limite, l'API répond 429.
"""

from rest_framework.throttling import AnonRateThrottle, ScopedRateThrottle, SimpleRateThrottle


class LoginIPThrottle(AnonRateThrottle):
    """Connexion : tentatives par adresse IP."""

    scope = "login_ip"


class LoginAccountThrottle(SimpleRateThrottle):
    """Connexion : tentatives sur un même compte, quelle que soit l'IP (attaque répartie)."""

    scope = "login_account"

    def get_cache_key(self, request, view):
        email = str(request.data.get("email", "")).strip().lower()
        return self.cache_format % {"scope": self.scope, "ident": email} if email else None


class RegisterThrottle(AnonRateThrottle):
    scope = "register"


class ScopedThrottle(ScopedRateThrottle):
    """Limite par action (throttle_scope sur la vue)."""
