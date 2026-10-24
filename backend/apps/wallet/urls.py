from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("wallet/payouts", views.PayoutViewSet, basename="payout")
router.register("wallet", views.WalletViewSet, basename="wallet")

urlpatterns = router.urls
