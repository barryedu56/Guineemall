from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("statuts", views.StatutViewSet, basename="statut")
router.register("vendor/statuts", views.VendorStatutViewSet, basename="vendor-statut")

urlpatterns = router.urls
