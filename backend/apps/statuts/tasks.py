from datetime import timedelta

from celery import shared_task
from django.utils import timezone

from .models import Statut


@shared_task
def purge_expired_statuts():
    """Supprime les statuts expirés depuis plus de 7 jours (et leurs fichiers)."""
    old = Statut.objects.filter(expires_at__lt=timezone.now() - timedelta(days=7))
    for statut in old:
        statut.media.delete(save=False)
    old.delete()
