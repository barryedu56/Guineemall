from django.urls import path
from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
for prefix, viewset in [
    ("vendors", views.VendorViewSet),
    ("verifications", views.VerificationViewSet),
    ("riders", views.RiderViewSet),
    ("users", views.UserViewSet),
    ("orders", views.OrderViewSet),
    ("payouts", views.PayoutViewSet),
    ("deposits", views.DepositViewSet),
    ("credit-refunds", views.CreditRefundViewSet),
    ("categories", views.CategoryViewSet),
    ("products", views.ProductViewSet),
    ("reviews", views.ReviewViewSet),
    ("coupons", views.CouponViewSet),
    ("campaigns", views.CampaignViewSet),
    ("zones", views.ZoneViewSet),
    ("pickup-points", views.PickupPointViewSet),
    ("audit", views.AuditLogViewSet),
]:
    router.register(f"backoffice/{prefix}", viewset, basename=f"backoffice-{prefix}")

urlpatterns = [
    path("backoffice/settings/", views.site_settings, name="backoffice-settings"),
    *router.urls,
]
