from datetime import timedelta

from celery import shared_task
from django.conf import settings
from django.utils import timezone

from apps.orders.models import VendorOrder

from .services import release_funds


@shared_task
def release_escrow():
    """Verse aux boutiques les paiements dont le client n'a pas confirmé la réception à temps."""
    limit = timezone.now() - timedelta(days=settings.ESCROW_AUTO_RELEASE_DAYS)
    for vendor_order in VendorOrder.objects.filter(
        status=VendorOrder.Status.DELIVERED, delivered_at__lt=limit, funds_released_at__isnull=True
    ):
        release_funds(vendor_order)


@shared_task
def check_transfers():
    """Envois automatiques (retraits, avoirs) : met à jour ceux que l'opérateur a terminés."""
    from .disbursement import check_transfers as run

    return run()


@shared_task
def send_transfer(kind, pk):
    """Lance l'envoi automatique d'un retrait (kind="payout") ou d'un avoir (kind="credit")."""
    from .disbursement import start_transfer
    from .models import CreditRefund, PayoutRequest

    model = PayoutRequest if kind == "payout" else CreditRefund
    obj = model.objects.filter(pk=pk).first()
    if obj:
        start_transfer(obj, "Retrait GuinéeMall" if kind == "payout" else "Remboursement GuinéeMall")
