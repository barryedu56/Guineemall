"""
Stockage privé des documents sensibles (pièces d'identité des vendeurs).

Ces fichiers sont rangés hors du dossier public `media/` : aucune adresse web ne les sert
directement. Ils ne sont lisibles que par un membre de l'équipe connecté, via une vue protégée.
"""

from django.conf import settings
from django.core.files.storage import FileSystemStorage


class PrivateStorage(FileSystemStorage):
    """Fichiers sans adresse publique : url() ne renvoie rien (consultation via la vue protégée)."""

    def url(self, name):
        return ""


def private_storage():
    return PrivateStorage(location=settings.PRIVATE_MEDIA_ROOT)
