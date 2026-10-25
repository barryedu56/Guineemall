"""
Double authentification (TOTP, Google Authenticator / Authy / Microsoft Authenticator).

Obligatoire pour l'équipe : sans elle, pas d'accès à l'administration. Une fois activée,
la connexion demande le code à 6 chiffres affiché par l'application, en plus du mot de passe.
"""

import base64
from io import BytesIO

import pyotp
import qrcode
from django.utils import timezone
from rest_framework import permissions, serializers, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.exceptions import ValidationError
from rest_framework.response import Response

from apps.core.audit import audit
from apps.core.models import SiteSettings


def verify(user, code):
    return bool(user.totp_secret) and pyotp.TOTP(user.totp_secret).verify(str(code or "").strip(), valid_window=1)


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def status_view(request):
    return Response({"enabled": request.user.has_2fa, "required": request.user.is_team})


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def setup(request):
    """Nouveau secret + QR code à scanner. Pas encore actif : il faut confirmer avec un code."""
    user = request.user
    if user.has_2fa:
        raise ValidationError({"detail": "La double authentification est déjà activée."})
    user.totp_secret = pyotp.random_base32()
    user.save(update_fields=["totp_secret"])
    uri = pyotp.TOTP(user.totp_secret).provisioning_uri(name=user.email, issuer_name=SiteSettings.load().site_name)
    image = qrcode.make(uri, box_size=6, border=2)
    buffer = BytesIO()
    image.save(buffer, format="PNG")
    return Response({
        "secret": user.totp_secret,
        "qr": "data:image/png;base64," + base64.b64encode(buffer.getvalue()).decode(),
    })


class CodeSerializer(serializers.Serializer):
    code = serializers.CharField(max_length=10)


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def enable(request):
    payload = CodeSerializer(data=request.data)
    payload.is_valid(raise_exception=True)
    if not verify(request.user, payload.validated_data["code"]):
        raise ValidationError({"code": "Code incorrect. Vérifiez l'heure de votre téléphone et réessayez."})
    request.user.totp_enabled_at = timezone.now()
    request.user.save(update_fields=["totp_enabled_at"])
    audit(request, "auth.2fa_enabled", request.user)
    return Response({"enabled": True})


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def disable(request):
    """Désactivation : le code actuel est exigé (un voleur de session ne peut pas l'enlever)."""
    payload = CodeSerializer(data=request.data)
    payload.is_valid(raise_exception=True)
    if not verify(request.user, payload.validated_data["code"]):
        raise ValidationError({"code": "Code incorrect."})
    request.user.totp_secret = ""
    request.user.totp_enabled_at = None
    request.user.save(update_fields=["totp_secret", "totp_enabled_at"])
    audit(request, "auth.2fa_disabled", request.user)
    return Response(status=status.HTTP_204_NO_CONTENT)
