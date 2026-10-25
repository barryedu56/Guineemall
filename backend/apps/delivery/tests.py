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
        self.assertEqual(self.client.get("/api/rider/deliveries/").status_code, 403)

    def test_boutique_sans_adresse_de_retrait_ne_peut_pas_marquer_pret(self):
        self.add(self.riz, 1)
        self.checkout()
        vo = VendorOrder.objects.get()
        self.madina.pickup_landmark = ""
        self.madina.save()
        self.client.force_authenticate(self.madina.user)
        self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": "preparing"})
        r = self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": "ready"})
        self.assertEqual(r.status_code, 400)
        self.assertIn("adresse de retrait", r.data["detail"])

    def test_boutique_qui_livre_elle_meme_annule_la_course(self):
        self.add(self.riz, 1)
        self.checkout()
        vo = VendorOrder.objects.get()
        self.client.force_authenticate(self.madina.user)
        for s in ["preparing", "ready", "delivered"]:
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
        self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": "preparing"})
        with self.captureOnCommitCallbacks(execute=True):
            self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": "ready"})
        self.vo.refresh_from_db()
        self.delivery = Delivery.objects.get(vendor_order=self.vo)

    def as_shop(self, action, method="post", **data):
        self.client.force_authenticate(self.madina.user)
        with self.captureOnCommitCallbacks(execute=True):
            url = f"/api/vendor/orders/{self.vo.pk}/" + (f"{action}/" if action else "")
            return getattr(self.client, method)(url, data)

    def as_rider(self, action, **data):
        self.client.force_authenticate(self.rider_user)
        with self.captureOnCommitCallbacks(execute=True):
            return self.client.post(f"/api/rider/deliveries/{self.delivery.pk}/{action}/", data)

    def offer_and_accept(self):
        self.assertEqual(self.as_shop("assign", rider=self.rider.pk).status_code, 200)
        self.assertEqual(self.as_rider("accept").status_code, 200)

    def test_code_genere_et_visible_du_seul_client(self):
        self.assertRegex(self.vo.delivery_code, r"^\d{4}$")
        self.assertIn(self.vo.delivery_code, self.user.notifications.latest("created_at").body)
        self.client.force_authenticate(self.user)
        order = self.client.get(f"/api/orders/{self.order_id}/").data
        self.assertEqual(order["vendor_orders"][0]["delivery_code"], self.vo.delivery_code)
        self.client.force_authenticate(self.madina.user)
        self.assertNotIn("delivery_code", self.client.get(f"/api/vendor/orders/{self.vo.pk}/").data)
        self.as_shop("assign", rider=self.rider.pk)
        self.client.force_authenticate(self.rider_user)
        self.assertNotIn(self.vo.delivery_code, str(self.client.get("/api/rider/deliveries/").data))

    def test_aucun_livreur_ne_voit_une_course_non_proposee(self):
        self.client.force_authenticate(self.rider_user)
        self.assertEqual(self.client.get("/api/rider/deliveries/").data["count"], 0)
        self.assertEqual(self.as_rider("accept").status_code, 404)
        self.assertFalse(self.rider_user.notifications.exists())

    def test_la_boutique_choisit_le_livreur(self):
        other = Rider.objects.create(
            user=User.objects.create_user(email="m2@test.gn", password="x", name="Loin", role="livreur"),
            is_approved=True, communes="Kaloum",  # ne dessert ni la boutique ni le client
        )
        candidates = self.as_shop("riders", method="get").data
        self.assertEqual([c["id"] for c in candidates], [self.rider.pk])
        self.assertEqual(self.as_shop("assign", rider=other.pk).status_code, 400)

        self.as_shop("assign", rider=self.rider.pk)
        self.assertTrue(self.rider_user.notifications.filter(title__contains="vous propose une course").exists())
        self.client.force_authenticate(self.rider_user)
        offered = self.client.get("/api/rider/deliveries/?etat=propositions").data["results"][0]
        # Avant d'accepter : où retirer le colis, mais pas les coordonnées du client
        self.assertEqual(offered["pickup"]["landmark"], "Face à la pharmacie")
        self.assertIsNone(offered["dropoff"]["phone"])
        self.assertIsNotNone(offered["offer_expires_at"])

        accepted = self.as_rider("accept").data
        self.assertEqual(accepted["dropoff"]["phone"], "620000000")
        self.assertTrue(self.madina.user.notifications.filter(title__contains="Ibrahima arrive").exists())
        courier = self.as_shop("", method="get").data["courier"]
        self.assertEqual((courier["status"], courier["rider"]["phone"]), ("assigned", "624000000"))

    def test_refus_puis_autre_livreur(self):
        self.as_shop("assign", rider=self.rider.pk)
        self.assertEqual(self.as_rider("decline").status_code, 204)
        self.delivery.refresh_from_db()
        self.assertEqual((self.delivery.status, self.delivery.rider), ("open", None))
        self.assertTrue(self.madina.user.notifications.filter(title__contains="a refusé").exists())
        self.assertTrue(self.as_shop("riders", method="get").data[0]["declined"])

    def test_proposition_expiree(self):
        from datetime import timedelta

        from django.utils import timezone

        from .services import expire_offers

        self.as_shop("assign", rider=self.rider.pk)
        Delivery.objects.filter(pk=self.delivery.pk).update(offered_at=timezone.now() - timedelta(minutes=11))
        self.assertEqual(self.as_rider("accept").status_code, 400)
        self.assertEqual(expire_offers(), 1)
        self.delivery.refresh_from_db()
        self.assertEqual(self.delivery.status, "open")

    def test_la_boutique_peut_reprendre_la_course(self):
        self.offer_and_accept()
        self.assertEqual(self.as_shop("withdraw").status_code, 200)
        self.assertTrue(self.rider_user.notifications.filter(title__contains="Course retirée").exists())
        self.offer_and_accept()
        self.as_rider("pickup")
        self.assertEqual(self.as_shop("withdraw").status_code, 400)  # colis déjà en main du livreur

    def test_livraison_impossible_sans_le_bon_code(self):
        self.offer_and_accept()
        self.as_rider("pickup")
        self.assertTrue(self.madina.user.notifications.filter(title__contains="Colis remis").exists())
        r = self.as_rider("deliver", code="0000" if self.vo.delivery_code != "0000" else "1111")
        self.assertEqual(r.status_code, 400)
        self.assertIn("incorrect", r.data["detail"])
        self.assertEqual(self.as_rider("deliver", code=self.vo.delivery_code).status_code, 200)
        self.vo.refresh_from_db()
        self.assertEqual(self.vo.status, "delivered")
        steps = {s["key"]: s["done"] for s in self.as_shop("", method="get").data["timeline"]}
        self.assertTrue(all(steps.values()), steps)

    def test_blocage_apres_5_codes_faux(self):
        self.offer_and_accept()
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
        self.offer_and_accept()
        self.client.force_authenticate(self.madina.user)
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/vendor/orders/{self.vo.pk}/status/", {"status": "delivered", "code": self.vo.delivery_code})
        self.assertEqual(r.status_code, 200)
        self.delivery.refresh_from_db()
        self.assertEqual(self.delivery.status, "cancelled")
        self.assertTrue(self.rider_user.notifications.filter(title__contains="Course annulée").exists())

    def test_desistement_avant_recuperation(self):
        self.offer_and_accept()
        self.assertEqual(self.as_rider("release").status_code, 204)
        self.delivery.refresh_from_db()
        self.assertEqual((self.delivery.status, self.delivery.rider), ("open", None))
        self.offer_and_accept()
        self.as_rider("pickup")
        self.assertEqual(self.as_rider("release").status_code, 400)  # colis en main : plus possible

    def test_plafond_d_especes(self):
        LedgerEntry.objects.create(user=self.rider_user, kind="adjustment", amount=-1_900_000)
        self.assertTrue(self.as_shop("riders", method="get").data[0]["cash_blocked"])
        r = self.as_shop("assign", rider=self.rider.pk)
        self.assertEqual(r.status_code, 400)
        self.assertIn("reverser", r.data["detail"])

    def test_discussion_boutique_livreur(self):
        url = f"/api/delivery/{self.delivery.pk}/messages/"
        self.client.force_authenticate(self.madina.user)
        self.assertEqual(self.client.post(url, {"content": "Bonjour"}).status_code, 400)  # pas encore de livreur
        self.as_shop("assign", rider=self.rider.pk)
        self.client.force_authenticate(self.madina.user)
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(url, {"content": "Le colis est au comptoir, demandez Aïssatou"})
        self.assertEqual(r.status_code, 201, r.data)
        self.assertTrue(self.rider_user.notifications.filter(title__contains="Message de Madina").exists())
        self.client.force_authenticate(self.rider_user)
        self.assertEqual(self.client.get("/api/rider/deliveries/").data["results"][0]["unread"], 1)
        messages = self.client.get(url).data
        self.assertEqual((len(messages), messages[0]["mine"]), (1, False))
        self.client.post(url, {"content": "J'arrive dans 10 minutes"})
        # Un inconnu ne lit pas la discussion
        self.client.force_authenticate(self.make_client("curieux@test.gn"))
        self.assertEqual(self.client.get(url).status_code, 404)

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
        self.offer_and_accept()
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
