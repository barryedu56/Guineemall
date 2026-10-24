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


class ReviewTests(GuineeMallTestCase):
    def setUp(self):
        from apps.orders.models import Order, OrderItem, VendorOrder

        self.vendor = self.make_vendor()
        self.product = self.make_product(self.vendor)
        self.user = self.make_client()
        order = Order.objects.create(user=self.user, total_amount=1, delivery_city="Conakry", delivery_landmark="x",
                                     delivery_phone="620")
        self.vo = VendorOrder.objects.create(order=order, vendor=self.vendor, total_amount=1)
        OrderItem.objects.create(order=order, vendor_order=self.vo, product=self.product, product_name="x",
                                 unit_price=1, quantity=1)
        self.url = f"/api/products/{self.product.slug}/reviews/"
        self.client.force_authenticate(self.user)

    def test_avis_seulement_apres_reception(self):
        self.assertEqual(self.client.post(self.url, {"rating": 5}).status_code, 400)
        self.vo.status = "delivered"
        self.vo.save()
        r = self.client.post(self.url, {"rating": 4, "comment": "Très bon téléphone"})
        self.assertEqual(r.status_code, 201, r.data)
        self.assertEqual(r.data["author"], "Client T.")
        self.assertEqual(self.client.post(self.url, {"rating": 5}).status_code, 400)  # un seul avis

        product = self.client.get(f"/api/products/{self.product.slug}/").data
        self.assertEqual((product["rating"], product["reviews_count"]), (4.0, 1))

        self.client.force_authenticate(self.vendor.user)
        review_id = self.client.get("/api/vendor/reviews/").data["results"][0]["id"]
        self.client.post(f"/api/vendor/reviews/{review_id}/reply/", {"reply": "Merci !"})
        self.client.logout()
        self.assertEqual(self.client.get(self.url).data["results"][0]["vendor_reply"], "Merci !")

    def test_vues_et_statistiques(self):
        self.client.get(f"/api/products/{self.product.slug}/")
        self.client.get(f"/api/products/{self.product.slug}/")
        self.client.force_authenticate(self.vendor.user)
        stats = self.client.get("/api/vendor/stats/").data
        self.assertEqual(stats["views"], 2)
        self.assertEqual(stats["orders"], 1)

    def test_image_de_partage(self):
        r = self.client.get(f"/api/products/{self.product.slug}/share-image/")
        self.assertEqual((r.status_code, r["Content-Type"]), (200, "image/jpeg"))


class ForYouTests(GuineeMallTestCase):
    def test_selection_selon_les_achats(self):
        from apps.catalog.models import Category
        from apps.orders.models import Order, OrderItem, VendorOrder

        vendor = self.make_vendor()
        mode, _ = Category.objects.get_or_create(name="Mode")
        achete = self.make_product(vendor, name="Pagne acheté", category=mode)
        similaire = self.make_product(vendor, name="Bazin", category=mode)
        self.make_product(vendor, name="Téléphone")  # autre catégorie
        user = self.make_client()
        order = Order.objects.create(user=user, total_amount=1, delivery_city="Conakry", delivery_landmark="x", delivery_phone="6")
        vo = VendorOrder.objects.create(order=order, vendor=vendor, total_amount=1)
        OrderItem.objects.create(order=order, vendor_order=vo, product=achete, product_name="x", unit_price=1, quantity=1)

        self.client.force_authenticate(user)
        names = [p["name"] for p in self.client.get("/api/products/for-you/").data]
        self.assertEqual(names[0], similaire.name)       # d'abord la catégorie achetée
        self.assertNotIn(achete.name, names)              # jamais un produit déjà acheté
        self.assertIn("Téléphone", names)                 # complété par les nouveautés
