from django.urls import path
from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("shops", views.VendorViewSet, basename="shop")

urlpatterns = [
    path("vendor/shop/", views.MyShopView.as_view(), name="my-shop"),
    *router.urls,
]
