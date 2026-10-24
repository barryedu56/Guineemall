from celery import shared_task

from .whatsapp import send_whatsapp


@shared_task(autoretry_for=(Exception,), retry_backoff=True, max_retries=3)
def send_whatsapp_task(phone, title, body):
    send_whatsapp(phone, title, body)
