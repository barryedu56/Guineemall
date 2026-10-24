from django.conf import settings
from django.conf.urls.static import static
from django.contrib import admin
from django.urls import include, path

from apps.core import admin_views
from apps.core.views import site_config

admin.site.site_header = "Administration GuinéeMall"
admin.site.site_title = "GuinéeMall"

API_APPS = [
    "vendors", "catalog", "orders", "statuts", "notifications", "payments",
    "wallet", "promotions", "delivery", "chat", "lives",
]

urlpatterns = [
    path("admin/", admin.site.urls),
    path("api/config/", site_config, name="site-config"),
    path("api/admin/dashboard/", admin_views.dashboard, name="admin-dashboard"),
    path("api/admin/session/", admin_views.open_admin_session, name="admin-session"),
    path("api/auth/", include("apps.accounts.urls")),
    *[path("api/", include(f"apps.{app}.urls")) for app in API_APPS],
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
