"""Enregistrement des actions sensibles dans le journal d'audit."""

from .models import AuditLog


def client_ip(request):
    if request is None:
        return None
    forwarded = request.META.get("HTTP_X_FORWARDED_FOR", "")
    return (forwarded.split(",")[0].strip() if forwarded else request.META.get("REMOTE_ADDR")) or None


def audit(request_or_user, action, target=None, data=None):
    """
    audit(request, "payout.paid", payout, {"amount": 50000})
    Accepte une requête (auteur + IP) ou directement un utilisateur (tâches, services).
    """
    request = request_or_user if hasattr(request_or_user, "META") else None
    actor = getattr(request, "user", None) if request else request_or_user
    if actor is not None and not getattr(actor, "is_authenticated", False):
        actor = None
    return AuditLog.objects.create(
        actor=actor,
        action=action,
        target=str(target)[:200] if target is not None else "",
        data=data or {},
        ip=client_ip(request),
    )
