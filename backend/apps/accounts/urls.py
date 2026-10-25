from django.urls import path
from rest_framework.routers import SimpleRouter
from rest_framework_simplejwt.views import TokenRefreshView

from . import password, two_factor, views

router = SimpleRouter()
router.register("addresses", views.AddressViewSet, basename="address")

urlpatterns = [
    path("register/", views.RegisterView.as_view(), name="register"),
    path("register/vendor/", views.VendorRegisterView.as_view(), name="register-vendor"),
    path("token/", views.LoginView.as_view(), name="token"),
    path("token/refresh/", TokenRefreshView.as_view(), name="token-refresh"),
    path("me/", views.MeView.as_view(), name="me"),
    path("referral/", views.ReferralView.as_view(), name="referral"),
    path("password/reset/", password.request_reset, name="password-reset"),
    path("password/reset/confirm/", password.confirm_reset, name="password-reset-confirm"),
    path("password/change/", password.change_password, name="password-change"),
    path("logout/", password.logout, name="logout"),
    path("2fa/", two_factor.status_view, name="2fa-status"),
    path("2fa/setup/", two_factor.setup, name="2fa-setup"),
    path("2fa/enable/", two_factor.enable, name="2fa-enable"),
    path("2fa/disable/", two_factor.disable, name="2fa-disable"),
    *router.urls,
]
