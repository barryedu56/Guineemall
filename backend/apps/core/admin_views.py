"""Tableau de bord d'administration (front React) et accès sans 2e connexion à l'admin Django."""

from datetime import timedelta

from django.contrib.auth import login
from django.db.models import Count, Q, Sum
from django.utils import timezone
from rest_framework import permissions, status
from rest_framework.decorators import api_view, permission_classes
from rest_framework.response import Response

from apps.accounts.models import User
from apps.delivery.models import Delivery, Rider
from apps.orders.models import Order, VendorOrder
from apps.vendors.models import Vendor, VerificationRequest
from apps.wallet.models import PayoutRequest


class IsPlatformAdmin(permissions.BasePermission):
    def has_permission(self, request, view):
        user = request.user
        return user.is_authenticated and (user.is_staff or user.role == User.Role.ADMIN)


@api_view(["POST"])
@permission_classes([IsPlatformAdmin])
def open_admin_session(request):
    """Ouvre la session de l'admin Django pour l'admin déjà connecté au site."""
    if not request.user.is_staff:
        return Response({"detail": "Ce compte n'a pas accès à l'administration Django."}, status=status.HTTP_403_FORBIDDEN)
    login(request, request.user, backend="django.contrib.auth.backends.ModelBackend")
    return Response(status=status.HTTP_204_NO_CONTENT)


def _money(queryset, field):
    return queryset.aggregate(total=Sum(field))["total"] or 0


@api_view(["GET"])
@permission_classes([IsPlatformAdmin])
def dashboard(request):
    now = timezone.now()
    today = now.replace(hour=0, minute=0, second=0, microsecond=0)
    month = now - timedelta(days=30)
    active = Order.objects.alive().exclude(status=Order.Status.CANCELLED)
    sold_month = VendorOrder.objects.filter(created_at__gte=month).exclude(status=VendorOrder.Status.CANCELLED)

    pending_payouts = PayoutRequest.objects.filter(status=PayoutRequest.Status.PENDING)
    return Response({
        "today": {
            "orders": active.filter(created_at__gte=today).count(),
            "revenue": _money(active.filter(created_at__gte=today), "subtotal"),
        },
        "month": {
            "orders": active.filter(created_at__gte=month).count(),
            "revenue": _money(active.filter(created_at__gte=month), "subtotal"),
            "new_users": User.objects.filter(date_joined__gte=month).count(),
            "delivered": sold_month.filter(status=VendorOrder.Status.DELIVERED).count(),
        },
        "totals": {
            "users": User.objects.count(),
            "clients": User.objects.filter(role=User.Role.CLIENT).count(),
            "shops": Vendor.objects.alive().filter(status=Vendor.Status.APPROVED).count(),
            "riders": Rider.objects.filter(is_approved=True).count(),
        },
        # Ce qui attend une action de l'équipe
        "todo": {
            "vendors_pending": Vendor.objects.alive().filter(status=Vendor.Status.PENDING).count(),
            "verifications_pending": VerificationRequest.objects.filter(status=VerificationRequest.Status.PENDING).count(),
            "riders_pending": Rider.objects.filter(is_approved=False).count(),
            "payouts_pending": pending_payouts.count(),
            "payouts_amount": _money(pending_payouts, "amount"),
            "refunds_pending": Order.objects.filter(payment_status=Order.PaymentStatus.REFUND_PENDING).count(),
            "deliveries_open": Delivery.objects.filter(status=Delivery.Status.OPEN).count(),
        },
        "orders_by_status": list(
            active.values("status").annotate(count=Count("id")).order_by("status")
        ),
        "recent_orders": [
            {
                "id": o.id, "client": o.user.name, "total": o.total_amount, "status": o.status,
                "status_label": o.get_status_display(), "payment_label": o.get_payment_method_display(),
                "created_at": o.created_at,
            }
            for o in Order.objects.alive().select_related("user").order_by("-created_at")[:8]
        ],
        "top_shops": list(
            Vendor.objects.alive().filter(status=Vendor.Status.APPROVED)
            .annotate(revenue=Sum("vendor_orders__subtotal", filter=Q(vendor_orders__created_at__gte=month)
                                  & ~Q(vendor_orders__status=VendorOrder.Status.CANCELLED)))
            .filter(revenue__gt=0).order_by("-revenue").values("shop_name", "slug", "revenue")[:5]
        ),
    })
