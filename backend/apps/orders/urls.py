from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("cart", views.CartViewSet, basename="cart")
router.register("orders", views.OrderViewSet, basename="order")
router.register("vendor/orders", views.VendorOrderViewSet, basename="vendor-order")

urlpatterns = router.urls
