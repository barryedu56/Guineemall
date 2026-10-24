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
