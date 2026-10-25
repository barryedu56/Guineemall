from datetime import timedelta

from django.core.files.uploadedfile import SimpleUploadedFile
from django.test import TestCase, override_settings
from django.utils import timezone

from apps.accounts.models import User
from apps.core.tests_utils import GuineeMallTestCase, image_file
from apps.vendors.models import Vendor

from .models import Statut

class StatutModelTests(TestCase):
    def setUp(self):
        super().setUp()
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


@override_settings(MEDIA_ROOT="/tmp/claude-1000/guineemall-test-media")
class StatutApiTests(GuineeMallTestCase):
    def setUp(self):
        super().setUp()
        self.vendor = self.make_vendor("Kipé")
        self.product = self.make_product(self.vendor)

    def publish(self, vendor, **extra):
        self.client.force_authenticate(vendor.user)
        media = image_file("promo.png")
        return self.client.post("/api/vendor/statuts/", {"media": media, "caption": "Promo !", **extra}, format="multipart")

    def test_publier_un_statut_lie_a_un_produit(self):
        r = self.publish(self.vendor, product_slug=self.product.slug)
        self.assertEqual(r.status_code, 201, r.data)
        self.assertEqual((r.data["media_type"], r.data["views_count"]), ("image", 0))

    def test_refuse_le_produit_d_une_autre_boutique(self):
        autre = self.make_product(self.make_vendor("Autre"), name="Pas à moi")
        r = self.publish(self.vendor, product_slug=autre.slug)
        self.assertEqual(r.status_code, 400)

    def test_refuse_un_fichier_qui_n_est_pas_un_media(self):
        self.client.force_authenticate(self.vendor.user)
        pdf = SimpleUploadedFile("doc.pdf", b"%PDF", content_type="application/pdf")
        r = self.client.post("/api/vendor/statuts/", {"media": pdf}, format="multipart")
        self.assertEqual(r.status_code, 400)

    def test_fil_des_statuts_et_vues(self):
        statut_id = self.publish(self.vendor).data["id"]
        Statut.objects.create(
            vendor=self.vendor, media="statuts/vieux.png", expires_at=timezone.now() - timedelta(hours=1)
        )
        client = self.make_client()
        self.client.force_authenticate(client)

        feed = self.client.get("/api/statuts/").data
        self.assertEqual(len(feed), 1)
        self.assertEqual(len(feed[0]["statuts"]), 1)  # le statut expiré n'apparaît pas
        self.assertFalse(feed[0]["all_seen"])

        self.client.post(f"/api/statuts/{statut_id}/view/")
        self.client.post(f"/api/statuts/{statut_id}/view/")  # vue comptée une seule fois
        self.assertTrue(self.client.get("/api/statuts/").data[0]["all_seen"])

        self.client.force_authenticate(self.vendor.user)
        self.assertEqual(self.client.get(f"/api/vendor/statuts/{statut_id}/").data["views_count"], 1)
        viewers = self.client.get(f"/api/vendor/statuts/{statut_id}/viewers/").data
        self.assertEqual([v["name"] for v in viewers], ["Client Test"])

    def test_boutique_non_vue_en_premier(self):
        autre = self.make_vendor("Madina")
        vu = self.publish(self.vendor).data["id"]
        self.publish(autre)
        self.client.force_authenticate(self.make_client())
        self.client.post(f"/api/statuts/{vu}/view/")
        feed = self.client.get("/api/statuts/").data
        self.assertEqual([g["vendor"]["shop_name"] for g in feed], ["Madina", "Kipé"])
