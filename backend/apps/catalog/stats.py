from datetime import timedelta

from django.db.models import Count, F, Sum
from django.db.models.functions import TruncDate
from django.utils import timezone

from .models import ProductDailyStat


def record_view(product_id):
    today = timezone.localdate()
    updated = ProductDailyStat.objects.filter(product_id=product_id, date=today).update(views=F("views") + 1)
    if not updated:
        ProductDailyStat.objects.get_or_create(product_id=product_id, date=today, defaults={"views": 1})


def vendor_dashboard(vendor, days=30):
    """Chiffres du tableau de bord vendeur sur les `days` derniers jours."""
    from apps.orders.models import OrderItem, VendorOrder
    from apps.wallet.models import LedgerEntry

    since = timezone.now() - timedelta(days=days)
    sold = VendorOrder.objects.filter(vendor=vendor, created_at__gte=since).exclude(status=VendorOrder.Status.CANCELLED)
    by_day = (
        sold.annotate(day=TruncDate("created_at")).values("day")
        .annotate(revenue=Sum("subtotal"), orders=Count("id")).order_by("day")
    )
    top = (
        OrderItem.objects.filter(vendor_order__in=sold)
        .values("product_name").annotate(sold=Sum("quantity"), revenue=Sum(F("unit_price") * F("quantity")))
        .order_by("-sold")[:5]
    )
    views = ProductDailyStat.objects.filter(product__vendor=vendor, date__gte=since.date()).aggregate(t=Sum("views"))["t"] or 0
    totals = sold.aggregate(revenue=Sum("subtotal"), orders=Count("id"))
    return {
        "days": days,
        "revenue": totals["revenue"] or 0,
        "orders": totals["orders"],
        "views": views,
        "conversion": round(totals["orders"] / views * 100, 1) if views else None,
        "balance": LedgerEntry.balance(vendor.user),
        "by_day": [{"day": d["day"].isoformat(), "revenue": d["revenue"], "orders": d["orders"]} for d in by_day],
        "top_products": list(top),
    }
