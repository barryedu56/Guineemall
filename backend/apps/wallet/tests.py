from datetime import timedelta

from django.test import override_settings
from django.utils import timezone

from apps.delivery.models import Delivery, Rider
from apps.accounts.models import User
from apps.orders.models import VendorOrder
from apps.orders.tests import OrderTestCase
from apps.core.tests_utils import DELIVERY_FEE

from .models import LedgerEntry, PayoutRequest
from .services import process_payout
from .tasks import release_escrow

RATE = 0.08


def balance(user):
    return LedgerEntry.balance(user)


@override_settings(PAYMENT_MODE="simulation", PLATFORM_COMMISSION_RATE=RATE, AFFILIATE_COMMISSION_RATE=0.02)
class EscrowTests(OrderTestCase):
    def deliver(self, vendor, vo):
        for s in ["confirmed", "preparing", "ready", "delivered"]:
            self.set_status(vendor, vo, s)

    def set_status(self, vendor, vo, new_status):
        self.client.force_authenticate(vendor.user)
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": new_status})
        self.client.force_authenticate(self.user)
        return r

    def paid_order(self, **extra):
        self.add(self.riz, 2)  # 700 000
        order_id = self.checkout("orange_money", **extra).data["id"]
        ref = self.client.post(f"/api/orders/{order_id}/pay/").data["reference"]
        self.client.post(f"/api/payments/simulation/{ref}/", {"result": "success"})
        return order_id, VendorOrder.objects.get(order_id=order_id)

    def test_paiement_en_ligne_bloque_jusqu_a_confirmation(self):
        order_id, vo = self.paid_order()
        self.deliver(self.madina, vo)
        self.assertEqual(balance(self.madina.user), 0)  # en séquestre

        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/orders/{order_id}/confirm-reception/")
        self.assertEqual(r.status_code, 200)
        commission = round(700_000 * RATE)
        self.assertEqual(balance(self.madina.user), 700_000 - commission + DELIVERY_FEE)

    def test_versement_automatique_apres_quelques_jours(self):
        _, vo = self.paid_order()
        self.deliver(self.madina, vo)
        VendorOrder.objects.filter(pk=vo.pk).update(delivered_at=timezone.now() - timedelta(days=4))
        release_escrow()
        release_escrow()  # deux passages : un seul versement
        self.assertEqual(LedgerEntry.objects.filter(user=self.madina.user).count(), 1)

    def test_paiement_a_la_livraison_la_boutique_doit_la_commission(self):
        self.add(self.riz, 2)
        self.checkout()
        vo = VendorOrder.objects.get(vendor=self.madina)
        self.deliver(self.madina, vo)
        self.assertEqual(balance(self.madina.user), -round(700_000 * RATE))

    def test_livreur_paye_et_dette_especes(self):
        self.add(self.riz, 1)
        self.checkout()
        vo = VendorOrder.objects.get(vendor=self.madina)
        for s in ["confirmed", "preparing", "ready"]:
            self.set_status(self.madina, vo, s)
        rider_user = User.objects.create_user(email="moto@test.gn", password="x", name="Ibrahima", role="livreur")
        rider = Rider.objects.create(user=rider_user, is_approved=True)
        delivery = Delivery.objects.get(vendor_order=vo)
        self.client.force_authenticate(rider_user)
        self.client.post(f"/api/rider/deliveries/{delivery.pk}/accept/")
        self.client.post(f"/api/rider/deliveries/{delivery.pk}/pickup/")
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/rider/deliveries/{delivery.pk}/deliver/")
        self.assertEqual(r.data["status"], "delivered")
        vo.refresh_from_db()
        self.assertEqual(vo.status, "delivered")
        # Le livreur a encaissé 370 000 en espèces et gagne 20 000 : il doit 350 000
        self.assertEqual(balance(rider_user), DELIVERY_FEE - (350_000 + DELIVERY_FEE))
        self.assertEqual(balance(self.madina.user), 350_000 - round(350_000 * RATE))

    def test_ambassadeur_touche_sa_commission(self):
        ambassadeur = self.make_client("amb@test.gn")
        order_id, vo = self.paid_order(affiliate_code=ambassadeur.referral_code)
        self.deliver(self.madina, vo)
        with self.captureOnCommitCallbacks(execute=True):
            self.client.post(f"/api/orders/{order_id}/confirm-reception/")
        self.assertEqual(balance(ambassadeur), round(700_000 * 0.02))

    def test_pas_de_commission_ambassadeur_pour_la_boutique_elle_meme(self):
        order_id, vo = self.paid_order(affiliate_code=self.madina.user.referral_code)
        self.deliver(self.madina, vo)
        with self.captureOnCommitCallbacks(execute=True):
            self.client.post(f"/api/orders/{order_id}/confirm-reception/")
        self.assertFalse(LedgerEntry.objects.filter(user=self.madina.user, kind="affiliate").exists())

    def test_on_ne_peut_pas_etre_son_propre_ambassadeur(self):
        order_id, _ = self.paid_order(affiliate_code=self.user.referral_code)
        self.assertIsNone(VendorOrder.objects.get(order_id=order_id).order.affiliate)


class PayoutTests(OrderTestCase):
    def test_retrait_limite_au_solde(self):
        LedgerEntry.objects.create(user=self.kipe.user, kind="adjustment", amount=100_000)
        self.client.force_authenticate(self.kipe.user)
        data = {"method": "orange_money", "phone": "620000000"}
        self.assertEqual(self.client.post("/api/wallet/payouts/", {**data, "amount": 150_000}).status_code, 400)
        self.assertEqual(self.client.post("/api/wallet/payouts/", {**data, "amount": 80_000}).status_code, 201)
        # 80 000 déjà demandés : il ne reste que 20 000 disponibles
        self.assertEqual(self.client.post("/api/wallet/payouts/", {**data, "amount": 30_000}).status_code, 400)
        self.assertEqual(self.client.get("/api/wallet/").data["available"], 20_000)

    def test_admin_valide_le_retrait(self):
        LedgerEntry.objects.create(user=self.kipe.user, kind="adjustment", amount=100_000)
        payout = PayoutRequest.objects.create(user=self.kipe.user, amount=60_000, method="orange_money", phone="620")
        admin = User.objects.create_superuser(email="admin@test.gn", password="x", name="Admin")
        process_payout(payout, admin, paid=True, reference="OM123")
        process_payout(payout, admin, paid=True)  # double clic : sans effet
        self.assertEqual(balance(self.kipe.user), 40_000)
