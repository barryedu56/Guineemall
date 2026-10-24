from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("lives", views.LiveViewSet, basename="live")
router.register("vendor/lives", views.VendorLiveViewSet, basename="vendor-live")

urlpatterns = router.urls
