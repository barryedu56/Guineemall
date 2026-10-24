from datetime import timedelta

from celery import shared_task
from django.conf import settings
from django.utils import timezone

from apps.orders.models import Order
from apps.orders.services import ONLINE_METHODS, expire_unpaid_order

from .models import Payment
from .services import refresh_status


@shared_task
def expire_unpaid_orders():
    """Vérifie une dernière fois les paiements en attente, puis annule les commandes impayées."""
    limit = timezone.now() - timedelta(minutes=settings.UNPAID_ORDER_TIMEOUT_MINUTES)
    for payment in Payment.objects.filter(status=Payment.Status.PENDING, created_at__lt=limit):
        refresh_status(payment)
        payment.refresh_from_db()
        if payment.status == Payment.Status.PENDING:
            payment.status = Payment.Status.EXPIRED
            payment.save(update_fields=["status", "updated_at"])

    expired = Order.objects.filter(
        payment_method__in=ONLINE_METHODS, payment_status=Order.PaymentStatus.PENDING, created_at__lt=limit
    ).exclude(status=Order.Status.CANCELLED)
    for order in expired:
        expire_unpaid_order(order)
