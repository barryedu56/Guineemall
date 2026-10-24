"""Mot de passe oublié : lien envoyé par email, valable quelques jours (jeton Django)."""

from django.conf import settings
from django.contrib.auth.password_validation import validate_password
from django.contrib.auth.tokens import default_token_generator
from django.core.mail import send_mail
from django.utils.encoding import force_bytes, force_str
from django.utils.http import urlsafe_base64_decode, urlsafe_base64_encode
from rest_framework import permissions, serializers, status
from rest_framework.decorators import api_view, permission_classes, throttle_classes
from rest_framework.response import Response
from rest_framework.throttling import AnonRateThrottle

from apps.core.models import SiteSettings

from .models import User


class ResetThrottle(AnonRateThrottle):
    rate = "5/hour"


@api_view(["POST"])
@permission_classes([permissions.AllowAny])
@throttle_classes([ResetThrottle])
def request_reset(request):
    email = str(request.data.get("email", "")).strip()
    user = User.objects.filter(email__iexact=email, is_active=True).first()
    # Même réponse que le compte existe ou non (ne révèle pas les emails inscrits)
    if user:
        uid = urlsafe_base64_encode(force_bytes(user.pk))
        token = default_token_generator.make_token(user)
        link = f"{settings.FRONTEND_URL}/mot-de-passe/nouveau?uid={uid}&token={token}"
        site = SiteSettings.load().site_name
        send_mail(
            f"{site} – réinitialisation du mot de passe",
            f"Bonjour {user.name},\n\nPour choisir un nouveau mot de passe, ouvrez ce lien :\n{link}\n\n"
            f"Si vous n'avez rien demandé, ignorez cet email.\n\nL'équipe {site}",
            None,
            [user.email],
        )
    return Response({"detail": "Si un compte existe avec cet email, un lien vient d'être envoyé."})


class ResetConfirmSerializer(serializers.Serializer):
    uid = serializers.CharField()
    token = serializers.CharField()
    password = serializers.CharField(validators=[validate_password])

    def validate(self, attrs):
        try:
            user = User.objects.get(pk=force_str(urlsafe_base64_decode(attrs["uid"])))
        except (User.DoesNotExist, ValueError, TypeError):
            user = None
        if user is None or not default_token_generator.check_token(user, attrs["token"]):
            raise serializers.ValidationError("Ce lien n'est plus valide. Refaites une demande.")
        attrs["user"] = user
        return attrs


@api_view(["POST"])
@permission_classes([permissions.AllowAny])
def confirm_reset(request):
    serializer = ResetConfirmSerializer(data=request.data)
    serializer.is_valid(raise_exception=True)
    user = serializer.validated_data["user"]
    user.set_password(serializer.validated_data["password"])
    user.save(update_fields=["password"])
    return Response(status=status.HTTP_204_NO_CONTENT)


class ChangePasswordSerializer(serializers.Serializer):
    current_password = serializers.CharField()
    new_password = serializers.CharField(validators=[validate_password])


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def change_password(request):
    serializer = ChangePasswordSerializer(data=request.data)
    serializer.is_valid(raise_exception=True)
    if not request.user.check_password(serializer.validated_data["current_password"]):
        raise serializers.ValidationError({"current_password": "Mot de passe actuel incorrect."})
    request.user.set_password(serializer.validated_data["new_password"])
    request.user.save(update_fields=["password"])
    return Response(status=status.HTTP_204_NO_CONTENT)
