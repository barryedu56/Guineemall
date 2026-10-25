from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("wallet/payouts", views.PayoutViewSet, basename="payout")
router.register("wallet/deposits", views.CashDepositViewSet, basename="deposit")
router.register("wallet/credit", views.CreditViewSet, basename="credit")
router.register("wallet", views.WalletViewSet, basename="wallet")

from django.urls import path

urlpatterns = [path("wallet/account/", views.PayoutAccountView.as_view(), name="payout-account"), *router.urls]
