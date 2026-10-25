from django.conf import settings
from django.conf.urls.static import static
from django.contrib import admin
from django.shortcuts import redirect
from django.urls import include, path

from apps.core import admin_views, private_views
from apps.core.views import site_config

admin.site.site_header = "Administration GuinéeMall"
admin.site.site_title = "GuinéeMall"


def admin_login(request, extra_context=None):
    """
    La page de connexion de l'admin Django (mot de passe seul) est fermée : on passe par la
    connexion du site, qui exige la double authentification, puis par le tableau de bord.
    """
    return redirect(f"{settings.FRONTEND_URL}/connexion?suivant=/administration")


admin.site.login = admin_login

API_APPS = [
    "vendors", "catalog", "orders", "statuts", "notifications", "payments",
    "wallet", "promotions", "delivery", "chat", "lives", "backoffice",
]

urlpatterns = [
    path(settings.ADMIN_URL, admin.site.urls),
    path("api/config/", site_config, name="site-config"),
    path("api/admin/dashboard/", admin_views.dashboard, name="admin-dashboard"),
    path("api/admin/session/", admin_views.open_admin_session, name="admin-session"),
    path(
        "private/verifications/<int:pk>/<str:field>/", private_views.verification_file,
        name="private-verification-file",
    ),
    path("api/auth/", include("apps.accounts.urls")),
    *[path("api/", include(f"apps.{app}.urls")) for app in API_APPS],
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
