from apps.core.tests_utils import GuineeMallTestCase
from apps.vendors.models import Vendor


class CatalogTests(GuineeMallTestCase):
    def test_seuls_les_produits_visibles_sont_listes(self):
        ok = self.make_vendor("Boutique OK")
        pending = self.make_vendor("Boutique Attente", status=Vendor.Status.PENDING)
        self.make_product(ok, name="Visible")
        self.make_product(ok, name="Inactif", status="inactive")
        self.make_product(ok, name="Supprimé").delete()
        self.make_product(pending, name="Boutique non approuvée")

        names = [p["name"] for p in self.client.get("/api/products/").data["results"]]
        self.assertEqual(names, ["Visible"])

    def test_recherche_et_filtre_prix(self):
        v = self.make_vendor()
        self.make_product(v, name="Riz parfumé 25kg", price=350_000)
        self.make_product(v, name="Riz local 50kg", price=500_000)
        self.make_product(v, name="Huile", price=100_000)
        r = self.client.get("/api/products/", {"search": "riz", "max_price": 400_000})
        self.assertEqual([p["name"] for p in r.data["results"]], ["Riz parfumé 25kg"])

    def test_un_vendeur_ne_voit_et_ne_modifie_que_ses_produits(self):
        moi, autre = self.make_vendor("Moi"), self.make_vendor("Autre")
        self.make_product(moi, name="À moi")
        produit_autre = self.make_product(autre, name="Pas à moi")
        self.client.force_authenticate(moi.user)

        names = [p["name"] for p in self.client.get("/api/vendor/products/").data["results"]]
        self.assertEqual(names, ["À moi"])
        r = self.client.patch(f"/api/vendor/products/{produit_autre.pk}/", {"price": 1})
        self.assertEqual(r.status_code, 404)

    def test_vendeur_en_attente_refuse(self):
        v = self.make_vendor(status=Vendor.Status.PENDING)
        self.client.force_authenticate(v.user)
        self.assertEqual(self.client.get("/api/vendor/products/").status_code, 403)

    def test_client_ne_peut_pas_acceder_a_l_espace_vendeur(self):
        self.client.force_authenticate(self.make_client())
        self.assertEqual(self.client.get("/api/vendor/products/").status_code, 403)

    def test_favoris(self):
        product = self.make_product(self.make_vendor())
        self.client.force_authenticate(self.make_client())
        self.client.post(f"/api/favorites/{product.slug}/")
        self.client.post(f"/api/favorites/{product.slug}/")  # deux fois : pas de doublon
        self.assertEqual(self.client.get("/api/favorites/").data["count"], 1)
        self.client.delete(f"/api/favorites/{product.slug}/")
        self.assertEqual(self.client.get("/api/favorites/").data["count"], 0)
