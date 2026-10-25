from django.contrib.admin.views.decorators import staff_member_required
from django.http import FileResponse, Http404
from django.shortcuts import get_object_or_404

from apps.core.audit import audit
from apps.vendors.models import VerificationRequest

FIELDS = {"document_front", "document_back", "shop_photo"}


@staff_member_required
def verification_file(request, pk, field):
    """Pièce d'identité d'un vendeur : réservée à l'équipe, chaque consultation est journalisée."""
    if field not in FIELDS:
        raise Http404
    vr = get_object_or_404(VerificationRequest, pk=pk)
    file = getattr(vr, field)
    if not file:
        raise Http404
    audit(request, "verification.document_viewed", vr, {"field": field})
    response = FileResponse(file.open("rb"))
    response["Cache-Control"] = "private, no-store"
    response["X-Content-Type-Options"] = "nosniff"
    return response
