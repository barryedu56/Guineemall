from rest_framework.permissions import BasePermission


class IsVendeur(BasePermission):
    """Vendeur connecté dont la boutique est approuvée (équivalent du middleware Laravel Vendeur)."""

    message = "Votre boutique doit être approuvée pour accéder à cet espace."

    def has_permission(self, request, view):
        user = request.user
        if not (user.is_authenticated and user.is_vendeur):
            return False
        vendor = getattr(user, "vendor", None)
        return vendor is not None and vendor.is_approved and not vendor.is_deleted


def get_vendor(request):
    return request.user.vendor
