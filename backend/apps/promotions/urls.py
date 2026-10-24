from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("campaigns", views.CampaignViewSet, basename="campaign")
router.register("vendor/coupons", views.VendorCouponViewSet, basename="vendor-coupon")

urlpatterns = router.urls
