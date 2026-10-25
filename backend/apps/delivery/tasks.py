from celery import shared_task

from .services import expire_offers


@shared_task(name="apps.delivery.tasks.expire_offers")
def expire_delivery_offers():
    """Propositions de course restées sans réponse du livreur."""
    return expire_offers()
