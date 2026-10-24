from apps.accounts.models import User
from apps.orders.tests import OrderTestCase

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
            "email": "moto@test.gn", "name": "Moto", "password": "MotDePasse123!", "phone": "620",
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
            self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": s})
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
            self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": s})
        self.assertEqual(Delivery.objects.get().status, "cancelled")
