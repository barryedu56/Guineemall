from datetime import timedelta

from django.test import TestCase
from django.utils import timezone

from apps.accounts.models import User
from apps.vendors.models import Vendor

from .models import Statut


class StatutTests(TestCase):
    def setUp(self):
        user = User.objects.create_user(email="v@test.gn", password="x", name="Vendeur", role=User.Role.VENDEUR)
        self.vendor = Vendor.objects.create(user=user, shop_name="Boutique Kipé")

    def test_expire_par_defaut_apres_24h(self):
        statut = Statut.objects.create(vendor=self.vendor, media="statuts/a.jpg")
        delta = statut.expires_at - statut.created_at
        self.assertAlmostEqual(delta.total_seconds(), 24 * 3600, delta=5)
        self.assertTrue(statut.is_active)

    def test_active_exclut_les_statuts_expires(self):
        actif = Statut.objects.create(vendor=self.vendor, media="statuts/a.jpg")
        Statut.objects.create(
            vendor=self.vendor, media="statuts/b.jpg", expires_at=timezone.now() - timedelta(minutes=1)
        )
        self.assertEqual(list(Statut.objects.active()), [actif])


class SlugTests(TestCase):
    def test_slug_unique_pour_deux_boutiques_de_meme_nom(self):
        u1 = User.objects.create_user(email="a@test.gn", password="x", name="A")
        u2 = User.objects.create_user(email="b@test.gn", password="x", name="B")
        v1 = Vendor.objects.create(user=u1, shop_name="Chez Mamadou")
        v2 = Vendor.objects.create(user=u2, shop_name="Chez Mamadou")
        self.assertEqual(v1.slug, "chez-mamadou")
        self.assertEqual(v2.slug, "chez-mamadou-2")
