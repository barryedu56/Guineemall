from django.urls import path
from rest_framework.routers import SimpleRouter
from rest_framework_simplejwt.views import TokenObtainPairView, TokenRefreshView

from . import password, views

router = SimpleRouter()
router.register("addresses", views.AddressViewSet, basename="address")

urlpatterns = [
    path("register/", views.RegisterView.as_view(), name="register"),
    path("register/vendor/", views.VendorRegisterView.as_view(), name="register-vendor"),
    path("token/", TokenObtainPairView.as_view(), name="token"),
    path("token/refresh/", TokenRefreshView.as_view(), name="token-refresh"),
    path("me/", views.MeView.as_view(), name="me"),
    path("referral/", views.ReferralView.as_view(), name="referral"),
    path("password/reset/", password.request_reset, name="password-reset"),
    path("password/reset/confirm/", password.confirm_reset, name="password-reset-confirm"),
    path("password/change/", password.change_password, name="password-change"),
    *router.urls,
]
