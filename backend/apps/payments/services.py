from django.conf import settings
from django.db import transaction

from apps.notifications.services import notify
from apps.orders.models import Order
from apps.orders.services import mark_order_paid

from .models import Payment
from .providers import PaymentError, get_provider


def start_payment(order, payer_phone=""):
    """Lance un paiement en ligne pour une commande. Renvoie (payment, PaymentInit)."""
    if order.payment_status == Order.PaymentStatus.PAID:
        raise PaymentError("Cette commande est déjà payée.")
    if order.status == Order.Status.CANCELLED:
        raise PaymentError("Cette commande a été annulée.")
    if order.payment_method in (Order.PaymentMethod.CASH_ON_DELIVERY, Order.PaymentMethod.STORE_CREDIT):
        raise PaymentError("Cette commande ne se paie pas en ligne.")

    provider = get_provider(order.payment_method)
    payment = Payment.objects.create(
        order=order, provider=order.payment_method, amount=order.amount_due, payer_phone=payer_phone
    )
    ref = payment.reference
    init = provider.initiate(
        payment,
        return_url=f"{settings.FRONTEND_URL}/commandes/{order.id}?paiement={ref}",
        cancel_url=f"{settings.FRONTEND_URL}/commandes/{order.id}?paiement=annule",
        notify_url=f"{settings.BACKEND_URL}/api/payments/callback/{ref}/",
    )
    payment.payment_url = init.redirect_url
    payment.provider_reference = init.provider_reference
    payment.pay_token = init.pay_token
    payment.notif_token = init.notif_token
    payment.raw_response = init.raw
    payment.save()
    return payment, init


@transaction.atomic
def apply_status(payment, status, reason=""):
    payment = Payment.objects.select_for_update().get(pk=payment.pk)
    if payment.status != Payment.Status.PENDING or status == "pending":
        return payment
    payment.status = Payment.Status.SUCCESS if status == "success" else Payment.Status.FAILED
    payment.failure_reason = reason
    payment.save(update_fields=["status", "failure_reason", "updated_at"])
    if payment.status == Payment.Status.SUCCESS:
        mark_order_paid(payment.order)
    else:
        notify(payment.order.user, "payment", "Paiement non abouti",
               "Votre paiement n'a pas pu être validé. Vous pouvez réessayer depuis votre commande.",
               f"/commandes/{payment.order_id}", whatsapp=False)
    return payment


def refresh_status(payment):
    """Interroge l'opérateur : on ne se fie jamais au contenu d'un rappel entrant."""
    if payment.status != Payment.Status.PENDING:
        return payment
    try:
        status = get_provider(payment.provider).check_status(payment)
    except PaymentError:
        return payment
    return apply_status(payment, status)
