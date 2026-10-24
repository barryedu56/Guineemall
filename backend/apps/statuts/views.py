from itertools import groupby

from django.db.models import Count
from rest_framework import mixins, permissions, status, viewsets
from rest_framework.decorators import action
from rest_framework.response import Response

from apps.core.permissions import IsVendeur, get_vendor
from apps.vendors.models import Vendor

from .models import Statut, StatutView
from .serializers import StatutSerializer, VendorStatutSerializer


def visible_statuts():
    return (
        Statut.objects.active()
        .filter(vendor__status=Vendor.Status.APPROVED, vendor__deleted_at__isnull=True)
        .select_related("vendor", "product")
    )


class StatutViewSet(viewsets.GenericViewSet):
    """
    GET /statuts/ : statuts actifs regroupés par boutique (comme la barre de statuts WhatsApp).
    Les boutiques dont le client n'a pas encore tout vu apparaissent en premier.
    POST /statuts/<id>/view/ : marque le statut comme vu.
    """

    serializer_class = StatutSerializer
    pagination_class = None

    def get_queryset(self):
        return visible_statuts()

    def list(self, request):
        statuts = list(self.get_queryset().order_by("vendor_id", "created_at"))
        seen_ids = set()
        if request.user.is_authenticated:
            seen_ids = set(
                StatutView.objects.filter(user=request.user, statut__in=statuts).values_list("statut_id", flat=True)
            )
        context = {**self.get_serializer_context(), "seen_ids": seen_ids}

        groups = []
        for _, items in groupby(statuts, key=lambda s: s.vendor_id):
            items = list(items)
            vendor = items[0].vendor
            data = StatutSerializer(items, many=True, context=context).data
            groups.append({
                "vendor": {
                    "slug": vendor.slug,
                    "shop_name": vendor.shop_name,
                    "logo": request.build_absolute_uri(vendor.logo.url) if vendor.logo else None,
                    "is_verified": vendor.is_verified,
                },
                "all_seen": all(s["seen"] for s in data),
                "latest_at": items[-1].created_at,
                "statuts": data,
            })
        groups.sort(key=lambda g: (g["all_seen"], -g["latest_at"].timestamp()))
        return Response(groups)

    @action(detail=True, methods=["post"], permission_classes=[permissions.IsAuthenticated])
    def view(self, request, pk=None):
        statut = self.get_object()
        if statut.vendor.user_id != request.user.id:
            StatutView.objects.get_or_create(statut=statut, user=request.user)
        return Response(status=status.HTTP_204_NO_CONTENT)


class VendorStatutViewSet(
    mixins.ListModelMixin, mixins.CreateModelMixin, mixins.RetrieveModelMixin,
    mixins.DestroyModelMixin, viewsets.GenericViewSet,
):
    """Espace vendeur : publier, lister, supprimer ses statuts et voir qui les a vus."""

    serializer_class = VendorStatutSerializer
    permission_classes = [IsVendeur]

    def get_queryset(self):
        return get_vendor(self.request).statuts.annotate(views_count=Count("views")).select_related("product")

    def get_serializer_context(self):
        context = super().get_serializer_context()
        if self.request.user.is_authenticated and hasattr(self.request.user, "vendor"):
            context["vendor"] = self.request.user.vendor
        return context

    def perform_create(self, serializer):
        statut = serializer.save(vendor=get_vendor(self.request))
        statut.views_count = 0  # pas d'annotation sur un objet tout juste créé

    @action(detail=True, methods=["get"])
    def viewers(self, request, pk=None):
        statut = self.get_object()
        views = statut.views.select_related("user").order_by("-viewed_at")
        return Response([{"name": v.user.name, "viewed_at": v.viewed_at} for v in views])
