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


class BlockedDeliveryBase(OrderTestCase):
    """Commande payée en ligne, colis prêt, que la boutique livre elle-même."""

    def setUp(self):
        super().setUp()
        self.add(self.riz, 1)
        self.order_id = self.checkout("orange_money").data["id"]
        ref = self.client.post(f"/api/orders/{self.order_id}/pay/").data["reference"]
        self.client.post(f"/api/payments/simulation/{ref}/", {"result": "success"})
        self.vo = VendorOrder.objects.get(order_id=self.order_id)
        self.client.force_authenticate(self.madina.user)
        for s in ["preparing", "ready"]:
            self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": s})
        self.vo.refresh_from_db()
        self.wrong = "0000" if self.vo.delivery_code != "0000" else "1111"

    def wrong_codes(self, n):
        self.client.force_authenticate(self.madina.user)
        for _ in range(n):
            self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": "delivered", "code": self.wrong})
        self.vo.refresh_from_db()


class BlockedDeliveryTests(BlockedDeliveryBase):
    """Codes faux répétés : le client est prévenu, répond lui-même, l'équipe tranche."""

    def test_client_prevenu_puis_bloque(self):
        self.wrong_codes(1)
        self.assertFalse(self.user.notifications.filter(title__contains="Mauvais code").exists())
        self.wrong_codes(1)
        self.assertTrue(self.user.notifications.filter(title__contains="Mauvais code").exists())
        self.wrong_codes(3)
        self.assertTrue(self.user.notifications.filter(title__contains="bloquée par sécurité").exists())
        self.client.force_authenticate(self.user)
        vo = self.client.get(f"/api/orders/{self.order_id}/").data["vendor_orders"][0]
        self.assertEqual((vo["code_blocked"], vo["delivery_code_attempts"]), (True, 5))

    def test_le_client_confirme_avoir_recu(self):
        from apps.wallet.models import LedgerEntry

        self.wrong_codes(5)
        self.client.force_authenticate(self.user)
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/orders/{self.order_id}/report/", {"vendor_order": self.vo.pk, "received": True})
        self.assertEqual(r.data["vendor_orders"][0]["status"], "delivered")
        self.assertTrue(LedgerEntry.objects.filter(user=self.madina.user, kind="sale").exists())  # boutique payée

    def test_le_client_n_a_rien_recu_puis_l_equipe_annule(self):
        from apps.accounts.models import User
        from apps.orders.disputes import admin_decision
        from apps.wallet.models import CreditEntry

        self.wrong_codes(5)
        self.client.force_authenticate(self.user)
        self.client.post(f"/api/orders/{self.order_id}/report/", {"vendor_order": self.vo.pk, "received": False})
        self.vo.refresh_from_db()
        self.assertEqual(self.vo.client_report, "not_received")
        admin = User.objects.create_superuser(email="adm@test.gn", password="x", name="Adm")
        admin_decision(admin, self.vo, "cancel", "Client joint : rien reçu")
        self.vo.refresh_from_db()
        self.assertEqual(self.vo.status, "cancelled")
        self.assertEqual(CreditEntry.balance(self.user), self.vo.total_amount)  # remboursé en avoir
        self.riz.refresh_from_db()
        self.assertEqual(self.riz.stock, 3)

    def test_boutique_suspecte_signalee(self):
        from apps.accounts.models import User

        admin = User.objects.create_superuser(email="adm@test.gn", password="x", name="Adm")
        self.wrong_codes(5)  # premier blocage
        other_order = Order.objects.create(user=self.user, total_amount=1, delivery_city="Conakry", delivery_landmark="x",
                                           delivery_phone="620000000")
        other = VendorOrder.objects.create(order=other_order, vendor=self.madina, total_amount=1, status="ready",
                                           delivery_code="1234", delivery_code_attempts=4)
        from apps.orders.services import OrderError, check_delivery_code

        with self.assertRaises(OrderError):
            check_delivery_code(other, "0000", by=self.madina.user)
        self.assertTrue(admin.notifications.filter(title__contains="Comportement suspect").exists())


class ClientWordPrevailsTests(BlockedDeliveryBase):
    """La parole du client prime : l'équipe ne peut pas valider à sa place un colis qu'il dit ne pas avoir reçu."""

    def setUp(self):
        super().setUp()
        from apps.accounts.models import User

        self.admin = User.objects.create_superuser(email="adm@test.gn", password="x", name="Adm")
        self.wrong_codes(5)

    def report(self, received):
        self.client.force_authenticate(self.user)
        with self.captureOnCommitCallbacks(execute=True):
            return self.client.post(f"/api/orders/{self.order_id}/report/", {"vendor_order": self.vo.pk, "received": received})

    def test_rien_recu_seul_le_client_peut_revenir_dessus(self):
        from apps.orders.disputes import admin_decision
        from apps.orders.services import OrderError

        self.report(False)
        self.vo.refresh_from_db()
        with self.assertRaises(OrderError):
            admin_decision(self.admin, self.vo, "received", "Il l'a reçu en fait")
        admin_decision(self.admin, self.vo, "ask_client", "Au téléphone il pense que son frère l'a reçu")
        self.assertTrue(self.user.notifications.filter(title__contains="avez-vous reçu votre colis").exists())
        self.assertEqual(self.report(True).data["vendor_orders"][0]["status"], "delivered")

    def test_validation_par_l_equipe_contestable(self):
        from apps.orders.disputes import admin_decision
        from apps.wallet.models import LedgerEntry
        from apps.wallet.services import release_funds

        admin_decision(self.admin, self.vo, "received", "Client joint, il a le colis")
        self.vo.refresh_from_db()
        self.assertEqual(self.vo.status, "delivered")
        self.assertIsNotNone(self.vo.contest_until)
        release_funds(self.vo)
        self.assertFalse(LedgerEntry.objects.exists())  # pas payée pendant le délai de contestation

        data = self.report(False).data["vendor_orders"][0]  # le client conteste
        self.assertTrue(data["contested"])
        VendorOrder.objects.filter(pk=self.vo.pk).update(contest_until=None)
        release_funds(self.vo)
        self.assertFalse(LedgerEntry.objects.exists())  # contestée : toujours gelée
        self.vo.refresh_from_db()
        admin_decision(self.admin, self.vo, "cancel", "Rien reçu, la boutique ne justifie pas la remise")
        self.vo.refresh_from_db()
        self.assertEqual(self.vo.status, "cancelled")

    def test_le_client_confirme_apres_validation(self):
        from apps.orders.disputes import admin_decision
        from apps.wallet.models import LedgerEntry

        admin_decision(self.admin, self.vo, "received", "Client joint, il a le colis")
        self.report(True)
        self.assertTrue(LedgerEntry.objects.filter(user=self.madina.user, kind="sale").exists())
