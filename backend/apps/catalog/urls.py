from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("categories", views.CategoryViewSet, basename="category")
router.register("products", views.ProductViewSet, basename="product")
router.register("favorites", views.FavoriteViewSet, basename="favorite")
router.register("vendor/products", views.VendorProductViewSet, basename="vendor-product")

urlpatterns = router.urls
