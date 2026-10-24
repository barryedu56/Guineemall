from django.conf import settings
from django.shortcuts import get_object_or_404
from rest_framework import permissions, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.exceptions import NotFound, ValidationError
from rest_framework.response import Response

from .models import Payment
from .services import apply_status, refresh_status


def payment_data(payment):
    return {
        "reference": str(payment.reference),
        "order_id": payment.order_id,
        "provider": payment.provider,
        "amount": payment.amount,
        "status": payment.status,
        "simulation": settings.PAYMENT_MODE != "live",
    }


@api_view(["GET"])
@permission_classes([permissions.IsAuthenticated])
def payment_status(request, reference):
    payment = get_object_or_404(Payment, reference=reference, order__user=request.user)
    return Response(payment_data(refresh_status(payment)))


@api_view(["GET", "POST"])
@permission_classes([permissions.AllowAny])
def provider_callback(request, reference):
    """
    URL appelée par Orange Money, MTN ou Stripe. Son contenu n'est pas utilisé
    (il pourrait être falsifié) : elle déclenche seulement une vérification auprès de l'opérateur.
    """
    payment = Payment.objects.filter(reference=reference).first()
    if payment:
        refresh_status(payment)
    return Response(status=status.HTTP_204_NO_CONTENT)


@api_view(["POST"])
@permission_classes([permissions.IsAuthenticated])
def simulate(request, reference):
    """Mode simulation uniquement : le client choisit le résultat du paiement."""
    if settings.PAYMENT_MODE == "live":
        raise NotFound()
    payment = get_object_or_404(Payment, reference=reference, order__user=request.user)
    result = request.data.get("result")
    if result not in ("success", "failed"):
        raise ValidationError({"result": "success ou failed"})
    return Response(payment_data(apply_status(payment, result, "Refus simulé" if result == "failed" else "")))
