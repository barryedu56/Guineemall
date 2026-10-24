from datetime import timedelta

from django.test import override_settings
from django.utils import timezone

from apps.orders.models import Order, VendorOrder
from apps.orders.tests import OrderTestCase

from .models import Payment
from .tasks import expire_unpaid_orders


@override_settings(PAYMENT_MODE="simulation")
class OnlinePaymentTests(OrderTestCase):
    def setUp(self):
        super().setUp()
        self.add(self.riz, 1)
        self.order_id = self.checkout("orange_money").data["id"]

    def pay(self):
        return self.client.post(f"/api/orders/{self.order_id}/pay/", {"phone": "620000001"})

    def test_commande_en_ligne_invisible_pour_le_vendeur_avant_paiement(self):
        self.client.force_authenticate(self.madina.user)
        self.assertEqual(self.client.get("/api/vendor/orders/").data["count"], 0)

    def test_paiement_reussi_debloque_la_commande(self):
        r = self.pay()
        self.assertIn("/paiement/simulation/", r.data["redirect_url"])
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/payments/simulation/{r.data['reference']}/", {"result": "success"})
        self.assertEqual(r.data["status"], "success")
        self.assertEqual(Order.objects.get(pk=self.order_id).payment_status, "paid")

        self.client.force_authenticate(self.madina.user)
        self.assertEqual(self.client.get("/api/vendor/orders/").data["count"], 1)
        self.assertTrue(self.madina.user.notifications.filter(title__contains="Nouvelle commande").exists())

    def test_paiement_refuse_puis_nouvel_essai(self):
        ref = self.pay().data["reference"]
        self.client.post(f"/api/payments/simulation/{ref}/", {"result": "failed"})
        self.assertEqual(Order.objects.get(pk=self.order_id).payment_status, "pending")
        self.assertEqual(self.pay().status_code, 200)  # le client peut réessayer
        self.assertEqual(Payment.objects.filter(order_id=self.order_id).count(), 2)

    def test_un_autre_client_ne_peut_pas_payer_ou_simuler(self):
        ref = self.pay().data["reference"]
        self.client.force_authenticate(self.make_client("intrus@test.gn"))
        self.assertEqual(self.client.post(f"/api/payments/simulation/{ref}/", {"result": "success"}).status_code, 404)

    def test_rappel_operateur_ne_change_rien_sans_verification(self):
        ref = self.pay().data["reference"]
        self.client.logout()
        self.client.post(f"/api/payments/callback/{ref}/", {"status": "SUCCESS"})  # contenu ignoré
        self.assertEqual(Payment.objects.get(reference=ref).status, "pending")

    def test_commande_impayee_annulee_et_stock_remis(self):
        self.pay()
        Order.objects.filter(pk=self.order_id).update(created_at=timezone.now() - timedelta(hours=1))
        Payment.objects.update(created_at=timezone.now() - timedelta(hours=1))
        expire_unpaid_orders()
        order = Order.objects.get(pk=self.order_id)
        self.assertEqual((order.status, order.payment_status), ("cancelled", "failed"))
        self.riz.refresh_from_db()
        self.assertEqual(self.riz.stock, 3)

    @override_settings(PAYMENT_MODE="live", ORANGE_MONEY={"CLIENT_ID": "", "MERCHANT_KEY": ""})
    def test_operateur_non_configure_message_clair(self):
        r = self.pay()
        self.assertEqual(r.status_code, 400)
        self.assertIn("pas encore configuré", r.data["detail"])

    def test_annulation_apres_paiement_signale_un_remboursement(self):
        ref = self.pay().data["reference"]
        self.client.post(f"/api/payments/simulation/{ref}/", {"result": "success"})
        self.client.post(f"/api/orders/{self.order_id}/cancel/")
        self.assertEqual(Order.objects.get(pk=self.order_id).payment_status, "refund_pending")
