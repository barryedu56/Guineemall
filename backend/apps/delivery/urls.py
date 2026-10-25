from django.urls import path
from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("delivery/zones", views.DeliveryZoneViewSet, basename="delivery-zone")
router.register("delivery/pickup-points", views.PickupPointViewSet, basename="pickup-point")
router.register("rider/deliveries", views.RiderDeliveryViewSet, basename="rider-delivery")

urlpatterns = [
    path("auth/register/rider/", views.RiderRegisterView.as_view(), name="register-rider"),
    path("rider/profile/", views.RiderProfileView.as_view(), name="rider-profile"),
    path("delivery/<int:pk>/rate/", views.rate_delivery, name="rate-delivery"),
    path("delivery/<int:pk>/messages/", views.DeliveryChatView.as_view(), name="delivery-messages"),
    *router.urls,
]
