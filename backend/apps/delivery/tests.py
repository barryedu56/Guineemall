from apps.accounts.models import User
from apps.orders.tests import OrderTestCase

from apps.orders.models import VendorOrder
from apps.wallet.models import CashDeposit, LedgerEntry

from .models import Delivery, PickupPoint, Rider


class DeliveryTests(OrderTestCase):
    def test_zone_non_desservie(self):
        self.address.commune = "Kaloum"
        self.address.save()
        self.add(self.riz, 1)
        r = self.checkout()
        self.assertEqual(r.status_code, 400)
        self.assertIn("pas encore disponible", r.data["detail"])

    def test_tarif_de_la_ville_si_commune_inconnue(self):
        from apps.delivery.models import DeliveryZone

        DeliveryZone.objects.create(city="Conakry", commune="", fee=30_000)
        self.address.commune = "Kaloum"
        self.address.save()
        self.add(self.riz, 1)
        self.assertEqual(self.checkout().data["delivery_fee"], 30_000)

    def test_point_relais(self):
        point = PickupPoint.objects.create(name="Relais Bambeto", city="Conakry", commune="Ratoma",
                                           address="Carrefour Bambeto", phone="620", fee=5_000)
        self.add(self.riz, 1)
        r = self.client.post("/api/orders/", {"pickup_point": point.pk, "payment_method": "cash_on_delivery"})
        self.assertEqual(r.status_code, 201, r.data)
        self.assertEqual((r.data["delivery_method"], r.data["delivery_fee"]), ("pickup", 5_000))
        self.assertEqual(r.data["pickup_point_name"], "Relais Bambeto")

    def test_livreur_non_valide_refuse(self):
        r = self.client.post("/api/auth/register/rider/", {
            "email": "moto@test.gn", "name": "Moto", "password": "MotDePasse123!", "phone": "620 45 67 89",
        })
        self.assertEqual(r.status_code, 201, r.data)
        self.client.force_authenticate(User.objects.get(email="moto@test.gn"))
        self.assertEqual(self.client.get("/api/rider/deliveries/available/").status_code, 403)

    def test_coordonnees_client_cachees_avant_acceptation(self):
        self.add(self.riz, 1)
        self.checkout()
        from apps.orders.models import VendorOrder

        vo = VendorOrder.objects.get()
        self.client.force_authenticate(self.madina.user)
        for s in ["confirmed", "preparing", "ready"]:
            self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": s, **({"code": VendorOrder.objects.get(pk=vo.pk).delivery_code} if s == "delivered" else {})})
        rider_user = User.objects.create_user(email="m@test.gn", password="x", name="M", role="livreur")
        Rider.objects.create(user=rider_user, is_approved=True)
        other_user = User.objects.create_user(email="m2@test.gn", password="x", name="M2", role="livreur")
        Rider.objects.create(user=other_user, is_approved=True)

        self.client.force_authenticate(rider_user)
        available = self.client.get("/api/rider/deliveries/available/").data
        self.assertEqual(len(available), 1)
        self.assertIsNone(available[0]["dropoff"]["phone"])
        self.assertEqual(available[0]["cash_to_collect"], 350_000 + 20_000)

        accepted = self.client.post(f"/api/rider/deliveries/{available[0]['id']}/accept/").data
        self.assertEqual(accepted["dropoff"]["phone"], "620000000")
        # Un second livreur ne peut plus la prendre
        self.client.force_authenticate(other_user)
        r = self.client.post(f"/api/rider/deliveries/{available[0]['id']}/accept/")
        self.assertEqual(r.status_code, 404)

    def test_boutique_qui_livre_elle_meme_annule_la_course(self):
        self.add(self.riz, 1)
        self.checkout()
        from apps.orders.models import VendorOrder

        vo = VendorOrder.objects.get()
        self.client.force_authenticate(self.madina.user)
        for s in ["confirmed", "preparing", "ready", "delivered"]:
            self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": s, **({"code": VendorOrder.objects.get(pk=vo.pk).delivery_code} if s == "delivered" else {})})
        self.assertEqual(Delivery.objects.get().status, "cancelled")



class RiderFlowTests(OrderTestCase):
    """Code de livraison, alertes, plafond d'espèces, désistement, versements, note."""

    def setUp(self):
        super().setUp()
        self.add(self.riz, 1)
        self.order_id = self.checkout().data["id"]  # paiement à la livraison : 350 000 + 20 000
        self.vo = VendorOrder.objects.get(order_id=self.order_id)
        self.rider_user = User.objects.create_user(email="moto@test.gn", password="x", name="Ibrahima", role="livreur", phone="624000000")
        self.rider = Rider.objects.create(user=self.rider_user, is_approved=True, communes="Ratoma")
        self.client.force_authenticate(self.madina.user)
        for s in ["confirmed", "preparing"]:
            self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": s})
        with self.captureOnCommitCallbacks(execute=True):
            self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": "ready"})
        self.vo.refresh_from_db()
        self.delivery = Delivery.objects.get(vendor_order=self.vo)

    def as_rider(self, action, **data):
        self.client.force_authenticate(self.rider_user)
        with self.captureOnCommitCallbacks(execute=True):
            return self.client.post(f"/api/rider/deliveries/{self.delivery.pk}/{action}/", data)

    def test_code_genere_et_visible_du_seul_client(self):
        self.assertRegex(self.vo.delivery_code, r"^\d{4}$")
        self.assertIn(self.vo.delivery_code, self.user.notifications.latest("created_at").body)
        self.client.force_authenticate(self.user)
        order = self.client.get(f"/api/orders/{self.order_id}/").data
        self.assertEqual(order["vendor_orders"][0]["delivery_code"], self.vo.delivery_code)
        self.client.force_authenticate(self.madina.user)
        self.assertNotIn("delivery_code", self.client.get(f"/api/vendor/orders/{self.vo.pk}/").data)
        self.client.force_authenticate(self.rider_user)
        self.assertNotIn(self.vo.delivery_code, str(self.client.get("/api/rider/deliveries/available/").data))

    def test_livreurs_prevenus_de_la_nouvelle_course(self):
        self.assertTrue(self.rider_user.notifications.filter(title__contains="Nouvelle course").exists())

    def test_livraison_impossible_sans_le_bon_code(self):
        self.as_rider("accept")
        self.as_rider("pickup")
        r = self.as_rider("deliver", code="0000" if self.vo.delivery_code != "0000" else "1111")
        self.assertEqual(r.status_code, 400)
        self.assertIn("incorrect", r.data["detail"])
        self.assertEqual(self.as_rider("deliver", code=self.vo.delivery_code).status_code, 200)
        self.vo.refresh_from_db()
        self.assertEqual(self.vo.status, "delivered")

    def test_blocage_apres_5_codes_faux(self):
        self.as_rider("accept")
        self.as_rider("pickup")
        wrong = "0000" if self.vo.delivery_code != "0000" else "1111"
        for _ in range(5):
            self.as_rider("deliver", code=wrong)
        r = self.as_rider("deliver", code=self.vo.delivery_code)  # même le bon code ne passe plus
        self.assertIn("bloquée", r.data["detail"])

    def test_la_boutique_doit_aussi_saisir_le_code(self):
        self.client.force_authenticate(self.madina.user)
        r = self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": "delivered"})
        self.assertEqual(r.status_code, 400)
        self.as_rider("accept")
        self.client.force_authenticate(self.madina.user)
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": "delivered", "code": self.vo.delivery_code})
        self.assertEqual(r.status_code, 200)
        self.delivery.refresh_from_db()
        self.assertEqual(self.delivery.status, "cancelled")
        self.assertTrue(self.rider_user.notifications.filter(title__contains="Course annulée").exists())

    def test_desistement_avant_recuperation(self):
        self.as_rider("accept")
        self.assertEqual(self.as_rider("release").status_code, 200)
        self.delivery.refresh_from_db()
        self.assertEqual((self.delivery.status, self.delivery.rider), ("open", None))
        self.as_rider("accept")
        self.as_rider("pickup")
        self.assertEqual(self.as_rider("release").status_code, 400)  # colis en main : plus possible

    def test_plafond_d_especes(self):
        LedgerEntry.objects.create(user=self.rider_user, kind="adjustment", amount=-1_900_000)
        self.client.force_authenticate(self.rider_user)
        available = self.client.get("/api/rider/deliveries/available/").data
        self.assertTrue(available[0]["cash_blocked"])
        r = self.as_rider("accept")
        self.assertEqual(r.status_code, 400)
        self.assertIn("reverser", r.data["detail"])

    def test_versement_d_especes_confirme_par_l_admin(self):
        from apps.wallet.services import process_deposit

        LedgerEntry.objects.create(user=self.rider_user, kind="adjustment", amount=-370_000)
        self.client.force_authenticate(self.rider_user)
        too_much = {"amount": 500_000, "method": "orange_money", "reference": "OM1"}
        self.assertEqual(self.client.post("/api/wallet/deposits/", too_much).status_code, 400)
        r = self.client.post("/api/wallet/deposits/", {**too_much, "amount": 370_000})
        self.assertEqual(r.status_code, 201)
        admin = User.objects.create_superuser(email="adm@test.gn", password="x", name="Adm")
        process_deposit(CashDeposit.objects.get(), admin, confirmed=True)
        self.assertEqual(self.client.get("/api/wallet/").data["debt"], 0)

    def test_note_du_livreur_par_le_client(self):
        self.as_rider("accept")
        self.as_rider("pickup")
        self.as_rider("deliver", code=self.vo.delivery_code)
        url = f"/api/delivery/{self.delivery.pk}/rate/"
        self.client.force_authenticate(self.make_client("autre@test.gn"))
        self.assertEqual(self.client.post(url, {"rating": 1}).status_code, 404)  # pas sa commande
        self.client.force_authenticate(self.user)
        self.assertEqual(self.client.post(url, {"rating": 5, "comment": "Rapide"}).status_code, 201)
        self.assertEqual(self.client.post(url, {"rating": 1}).status_code, 400)  # une seule note
        self.client.force_authenticate(self.rider_user)
        self.assertEqual(self.client.get("/api/rider/profile/").data["rating"]["rating"], 5)
