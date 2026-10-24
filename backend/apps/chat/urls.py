from rest_framework.routers import SimpleRouter

from . import views

router = SimpleRouter()
router.register("conversations", views.ConversationViewSet, basename="conversation")

urlpatterns = router.urls
