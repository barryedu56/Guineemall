from apps.core.tests_utils import DELIVERY_FEE, GuineeMallTestCase
from apps.orders.models import Order, VendorOrder


class OrderTestCase(GuineeMallTestCase):
    """Deux boutiques, un produit chacune, un client avec une adresse."""

    def setUp(self):
        super().setUp()
        self.user = self.make_client()
        self.address = self.make_address(self.user)
        self.kipe = self.make_vendor("Kipé")
        self.madina = self.make_vendor("Madina")
        self.tel = self.make_product(self.kipe, name="Téléphone", price=1_500_000, stock=5)
        self.riz = self.make_product(self.madina, name="Riz", price=350_000, stock=3)
        self.client.force_authenticate(self.user)

    def add(self, product, quantity):
        return self.client.post("/api/cart/", {"product_slug": product.slug, "quantity": quantity})

    def checkout(self, payment_method="cash_on_delivery", **extra):
        return self.client.post("/api/orders/", {"address": self.address.pk, "payment_method": payment_method, **extra})


class CheckoutTests(OrderTestCase):
    def test_panier_cumule_les_quantites(self):
        self.add(self.tel, 1)
        self.add(self.tel, 2)
        cart = self.client.get("/api/cart/").data
        self.assertEqual(len(cart["items"]), 1)
        self.assertEqual(cart["total"], 3 * 1_500_000)

    def test_panier_refuse_plus_que_le_stock(self):
        self.assertEqual(self.add(self.riz, 4).status_code, 400)

    def test_commande_decoupee_par_boutique(self):
        self.add(self.tel, 2)
        self.add(self.riz, 1)
        r = self.checkout()
        self.assertEqual(r.status_code, 201, r.data)

        # Chaque boutique livre sa partie : frais de livraison x 2
        self.assertEqual(r.data["total_amount"], 2 * 1_500_000 + 350_000 + 2 * DELIVERY_FEE)
        self.assertEqual(r.data["delivery_landmark"], "Derrière la mosquée")
        self.assertEqual(sorted(vo["shop_name"] for vo in r.data["vendor_orders"]), ["Kipé", "Madina"])
        self.tel.refresh_from_db()
        self.assertEqual(self.tel.stock, 3)
        self.assertEqual(self.client.get("/api/cart/").data["items"], [])

    def test_commande_refusee_si_stock_epuise_entre_temps(self):
        self.add(self.riz, 3)
        self.riz.stock = 1
        self.riz.save()
        r = self.checkout()
        self.assertEqual(r.status_code, 400)
        self.assertIn("Stock insuffisant", r.data["detail"])
        self.assertFalse(Order.objects.exists())

    def test_adresse_d_un_autre_client_refusee(self):
        autre = self.make_address(self.make_client("autre@test.gn"))
        self.add(self.riz, 1)
        r = self.client.post("/api/orders/", {"address": autre.pk, "payment_method": "cash_on_delivery"})
        self.assertEqual(r.status_code, 400)

    def test_annulation_client_remet_le_stock(self):
        self.add(self.riz, 2)
        order_id = self.checkout().data["id"]
        r = self.client.post(f"/api/orders/{order_id}/cancel/")
        self.assertEqual(r.data["status"], "cancelled")
        self.riz.refresh_from_db()
        self.assertEqual(self.riz.stock, 3)

    def test_annulation_client_partielle_si_une_boutique_prepare_deja(self):
        self.add(self.tel, 1)
        self.add(self.riz, 1)
        order_id = self.checkout().data["id"]
        vo_kipe = VendorOrder.objects.get(vendor=self.kipe)
        vo_kipe.status = VendorOrder.Status.PREPARING
        vo_kipe.save()

        r = self.client.post(f"/api/orders/{order_id}/cancel/")
        statuses = {vo["shop_name"]: vo["status"] for vo in r.data["vendor_orders"]}
        self.assertEqual(statuses, {"Kipé": "preparing", "Madina": "cancelled"})
        self.tel.refresh_from_db()
        self.riz.refresh_from_db()
        self.assertEqual((self.tel.stock, self.riz.stock), (4, 3))  # seul le riz revient


class VendorOrderTests(OrderTestCase):
    def setUp(self):
        super().setUp()
        self.add(self.tel, 1)
        self.add(self.riz, 1)
        self.order_id = self.checkout().data["id"]
        self.vo_kipe = VendorOrder.objects.get(vendor=self.kipe)
        self.vo_madina = VendorOrder.objects.get(vendor=self.madina)

    def set_status(self, vendor, vo, new_status):
        self.client.force_authenticate(vendor.user)
        with self.captureOnCommitCallbacks(execute=True):
            return self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": new_status, **({"code": VendorOrder.objects.get(pk=vo.pk).delivery_code} if new_status == "delivered" else {})})

    def test_parcours_complet_jusqu_a_livree(self):
        for s in ["preparing", "ready", "delivered"]:
            self.assertEqual(self.set_status(self.kipe, self.vo_kipe, s).status_code, 200)
        self.set_status(self.madina, self.vo_madina, "cancelled")
        order = Order.objects.get(pk=self.order_id)
        self.assertEqual(order.status, "delivered")

    def test_transition_interdite(self):
        r = self.set_status(self.kipe, self.vo_kipe, "delivered")
        self.assertEqual(r.status_code, 400)

    def test_commande_passe_en_cours_des_qu_une_boutique_confirme(self):
        self.set_status(self.kipe, self.vo_kipe, "preparing")
        self.assertEqual(Order.objects.get(pk=self.order_id).status, "processing")

    def test_annulation_vendeur_remet_le_stock(self):
        self.set_status(self.madina, self.vo_madina, "cancelled")
        self.riz.refresh_from_db()
        self.assertEqual(self.riz.stock, 3)

    def test_vendeur_ne_touche_pas_aux_commandes_des_autres(self):
        r = self.set_status(self.kipe, self.vo_madina, "preparing")
        self.assertEqual(r.status_code, 404)

    def test_vendeur_voit_l_adresse_de_livraison(self):
        self.client.force_authenticate(self.kipe.user)
        data = self.client.get("/api/vendor/orders/").data["results"][0]
        self.assertEqual(data["delivery"]["quartier"], "Kipé")
        self.assertEqual(data["next_statuses"], ["preparing", "cancelled"])


class OwnShopTests(OrderTestCase):
    """Un vendeur reste un client des autres boutiques, mais pas de la sienne."""

    def test_vendeur_ne_peut_pas_acheter_ses_produits(self):
        self.client.force_authenticate(self.kipe.user)
        r = self.client.post("/api/cart/", {"product_slug": self.tel.slug, "quantity": 1})
        self.assertEqual(r.status_code, 400)
        self.assertIn("propre boutique", str(r.data))
        # Il peut acheter chez une autre boutique
        self.assertEqual(self.client.post("/api/cart/", {"product_slug": self.riz.slug, "quantity": 1}).status_code, 201)

    def test_commande_refusee_si_le_panier_contient_ses_produits(self):
        from apps.orders.models import CartItem

        CartItem.objects.create(user=self.kipe.user, product=self.tel, quantity=1)  # ajouté avant la règle
        self.client.force_authenticate(self.kipe.user)
        address = self.make_address(self.kipe.user)
        r = self.client.post("/api/orders/", {"address": address.pk, "payment_method": "cash_on_delivery"})
        self.assertEqual(r.status_code, 400)
        self.assertIn("votre boutique", r.data["detail"])

    def test_panier_nettoye_de_ses_propres_produits(self):
        from apps.orders.models import CartItem

        CartItem.objects.create(user=self.kipe.user, product=self.tel, quantity=1)
        CartItem.objects.create(user=self.kipe.user, product=self.riz, quantity=1)
        self.client.force_authenticate(self.kipe.user)
        items = self.client.get("/api/cart/").data["items"]
        self.assertEqual([i["product"]["slug"] for i in items], [self.riz.slug])

    def test_pas_de_favori_sur_ses_produits(self):
        self.client.force_authenticate(self.kipe.user)
        self.assertEqual(self.client.post(f"/api/favorites/{self.tel.slug}/").status_code, 400)

    def test_produit_d_une_boutique_non_approuvee_hors_panier(self):
        self.madina.status = "suspended"
        self.madina.save()
        self.assertEqual(self.add(self.riz, 1).status_code, 400)
