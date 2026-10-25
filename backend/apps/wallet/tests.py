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
        for s in ["preparing", "ready", "delivered"]:
            self.set_status(vendor, vo, s)

    def set_status(self, vendor, vo, new_status):
        self.client.force_authenticate(vendor.user)
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": new_status, **({"code": VendorOrder.objects.get(pk=vo.pk).delivery_code} if new_status == "delivered" else {})})
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

    def test_paiement_a_la_livraison_hors_de_guineemall(self):
        self.add(self.riz, 2)
        self.checkout()
        vo = VendorOrder.objects.get(vendor=self.madina)
        self.deliver(self.madina, vo)
        self.assertFalse(LedgerEntry.objects.exists())  # ni commission, ni dette

    def test_paiement_a_la_livraison_reduction_guineemall_remboursee(self):
        from apps.promotions.models import Coupon

        Coupon.objects.create(code="TABASKI", type="fixed", value=50_000)  # code de la plateforme
        self.add(self.riz, 2)
        self.checkout(coupon_code="TABASKI")
        vo = VendorOrder.objects.get(vendor=self.madina)
        self.assertEqual(vo.platform_discount, 50_000)
        self.deliver(self.madina, vo)
        self.assertEqual(balance(self.madina.user), 50_000)

    def test_livreur_paye_et_dette_especes(self):
        self.add(self.riz, 1)
        self.checkout()
        vo = VendorOrder.objects.get(vendor=self.madina)
        for s in ["preparing", "ready"]:
            self.set_status(self.madina, vo, s)
        rider_user = User.objects.create_user(email="moto@test.gn", password="x", name="Ibrahima", role="livreur")
        rider = Rider.objects.create(user=rider_user, is_approved=True)
        delivery = Delivery.objects.get(vendor_order=vo)
        self.client.force_authenticate(self.madina.user)
        self.client.post(f"/api/vendor/orders/{vo.pk}/assign/", {"rider": rider.pk})
        self.client.force_authenticate(rider_user)
        self.client.post(f"/api/rider/deliveries/{delivery.pk}/accept/")
        self.client.post(f"/api/rider/deliveries/{delivery.pk}/pickup/")
        with self.captureOnCommitCallbacks(execute=True):
            r = self.client.post(f"/api/rider/deliveries/{delivery.pk}/deliver/", {"code": VendorOrder.objects.get(pk=vo.pk).delivery_code})
        self.assertEqual(r.data["status"], "delivered")
        vo.refresh_from_db()
        self.assertEqual(vo.status, "delivered")
        # Espèces : rien ne passe par GuinéeMall. Le livreur a encaissé 370 000, garde 20 000 et remet 350 000
        self.assertFalse(LedgerEntry.objects.exists())
        self.assertEqual(r.data["cash_for_shop"], 350_000)
        self.assertTrue(self.madina.user.notifications.filter(title__contains="vous doit").exists())
        self.client.force_authenticate(self.madina.user)
        r = self.client.post(f"/api/vendor/orders/{vo.pk}/cash-received/")
        self.assertIsNotNone(r.data["courier"]["cash_handed_at"])
        self.assertTrue(rider_user.notifications.filter(title__contains="Argent bien reçu").exists())

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


def verified_account(user, method="orange_money", phone="620000000"):
    from .models import PayoutAccount

    return PayoutAccount.objects.create(user=user, method=method, phone=phone, holder_name=user.name, status="verified")


class PayoutTests(OrderTestCase):
    def test_retrait_limite_au_solde(self):
        LedgerEntry.objects.create(user=self.kipe.user, kind="adjustment", amount=100_000)
        verified_account(self.kipe.user)
        self.client.force_authenticate(self.kipe.user)
        self.assertEqual(self.client.post("/api/wallet/payouts/", {"amount": 150_000}).status_code, 400)
        r = self.client.post("/api/wallet/payouts/", {"amount": 80_000, "phone": "666666666"})
        self.assertEqual(r.status_code, 201)
        self.assertEqual(r.data["phone"], "620000000")  # toujours le numéro vérifié, jamais celui envoyé
        # 80 000 déjà demandés : il ne reste que 20 000 disponibles
        self.assertEqual(self.client.post("/api/wallet/payouts/", {"amount": 30_000}).status_code, 400)
        self.assertEqual(self.client.get("/api/wallet/").data["available"], 20_000)

    def test_admin_valide_le_retrait(self):
        LedgerEntry.objects.create(user=self.kipe.user, kind="adjustment", amount=100_000)
        payout = PayoutRequest.objects.create(user=self.kipe.user, amount=60_000, method="orange_money", phone="620")
        admin = User.objects.create_superuser(email="admin@test.gn", password="x", name="Admin")
        process_payout(payout, admin, paid=True, reference="OM123")
        process_payout(payout, admin, paid=True)  # double clic : sans effet
        self.assertEqual(balance(self.kipe.user), 40_000)


class PayoutAccountTests(OrderTestCase):
    """Numéro de retrait vérifié : un voleur de compte ne peut pas détourner l'argent."""

    def setUp(self):
        super().setUp()
        self.kipe.user.set_password("MotDePasse123!")
        self.kipe.user.save()
        LedgerEntry.objects.create(user=self.kipe.user, kind="adjustment", amount=100_000)
        self.client.force_authenticate(self.kipe.user)
        self.admin = User.objects.create_superuser(email="admin@test.gn", password="x", name="Admin")

    def save_account(self, phone="620000000", password="MotDePasse123!"):
        return self.client.put("/api/wallet/account/", {"method": "orange_money", "phone": phone,
                                                         "holder_name": "Mamadou Diallo", "password": password})

    def test_parcours_numero_verifie(self):
        from .accounts import verify_account
        from .models import PayoutAccount

        self.assertIn("Enregistrez", self.client.post("/api/wallet/payouts/", {"amount": 10_000}).data["detail"])
        self.assertEqual(self.save_account(password="faux").status_code, 400)
        self.assertEqual(self.save_account().data["status"], "pending")
        self.assertIn("vérification", self.client.post("/api/wallet/payouts/", {"amount": 10_000}).data["detail"])
        verify_account(PayoutAccount.objects.get(), self.admin, True)
        self.assertEqual(self.client.post("/api/wallet/payouts/", {"amount": 10_000}).status_code, 201)

        # Changement de numéro : nouvelle vérification + 48 h de blocage, l'utilisateur est alerté
        self.assertEqual(self.save_account("666000000").data["status"], "pending")
        self.assertTrue(self.kipe.user.notifications.filter(title="Numéro de retrait modifié").exists())
        verify_account(PayoutAccount.objects.get(), self.admin, True)
        self.assertIn("modifié récemment", self.client.post("/api/wallet/payouts/", {"amount": 10_000}).data["detail"])

    @override_settings(PAYOUT_MODE="live", MTN_MOMO_DISBURSEMENT={"SUBSCRIPTION_KEY": "k", "API_USER": "u", "API_KEY": "s"})
    def test_envoi_automatique_mtn(self):
        from unittest.mock import MagicMock, patch

        from .disbursement import check_transfers

        verified_account(self.kipe.user, method="mtn_momo", phone="664000000")
        token = MagicMock(status_code=200, json=lambda: {"access_token": "t", "expires_in": 3600})
        sent = MagicMock(status_code=202)
        with patch("apps.wallet.disbursement.requests.post", side_effect=[token, sent]) as post, \
                self.captureOnCommitCallbacks(execute=True):
            r = self.client.post("/api/wallet/payouts/", {"amount": 60_000})
        self.assertEqual(post.call_args.kwargs["json"]["payee"]["partyId"], "224664000000")
        payout = PayoutRequest.objects.get()
        self.assertEqual(payout.status, "sending")
        self.assertEqual(r.status_code, 201)
        # L'équipe ne peut pas payer une deuxième fois à la main pendant l'envoi
        self.admin.totp_secret, self.admin.totp_enabled_at = "JBSWY3DPEHPK3PXP", timezone.now()
        self.admin.save()
        self.client.force_authenticate(self.admin)
        self.assertEqual(self.client.post(f"/api/backoffice/payouts/{payout.pk}/pay/", {"reference": "X"}).status_code, 400)

        with patch("apps.wallet.disbursement.requests.get", return_value=MagicMock(content=b"1", json=lambda: {"status": "SUCCESSFUL"})):
            self.assertEqual(check_transfers(), 1)
        payout.refresh_from_db()
        self.assertEqual(payout.status, "paid")
        self.assertEqual(balance(self.kipe.user), 40_000)

    def test_orange_reste_manuel_sans_contrat(self):
        verified_account(self.kipe.user)
        with self.captureOnCommitCallbacks(execute=True):
            self.client.post("/api/wallet/payouts/", {"amount": 60_000})
        self.assertEqual(PayoutRequest.objects.get().status, "pending")


@override_settings(PAYMENT_MODE="simulation")
class StoreCreditTests(OrderTestCase):
    """Avoir : alimenté par les remboursements, dépensé à la commande, rendu sur Mobile Money à la demande."""

    def give_credit(self, amount):
        from .models import CreditEntry

        CreditEntry.objects.create(user=self.user, kind="refund", amount=amount)

    def credit(self):
        return self.client.get("/api/wallet/credit/").data["balance"]

    def test_avoir_couvre_toute_la_commande(self):
        self.give_credit(1_000_000)
        self.add(self.riz, 1)  # 350 000 + 20 000
        self.assertEqual(self.client.post("/api/orders/quote/", {"address": self.address.pk}).data["credit_available"], 1_000_000)
        r = self.checkout("orange_money", use_credit=True)
        self.assertEqual(r.status_code, 201, r.data)
        self.assertEqual((r.data["payment_method"], r.data["payment_status"], r.data["amount_due"]), ("store_credit", "paid", 0))
        self.assertEqual(self.credit(), 1_000_000 - 370_000)
        # La boutique la voit tout de suite, comme une commande payée en ligne (séquestre)
        self.client.force_authenticate(self.madina.user)
        self.assertEqual(self.client.get("/api/vendor/orders/").data["count"], 1)

    def test_avoir_partiel_complete_en_ligne(self):
        from apps.payments.models import Payment

        self.give_credit(100_000)
        self.add(self.riz, 1)
        self.assertEqual(self.checkout("cash_on_delivery", use_credit=True).status_code, 400)  # pas avec les espèces
        r = self.checkout("orange_money", use_credit=True)
        self.assertEqual((r.data["credit_used"], r.data["amount_due"]), (100_000, 270_000))
        self.client.post(f"/api/orders/{r.data['id']}/pay/")
        self.assertEqual(Payment.objects.get().amount, 270_000)
        # Commande abandonnée avant paiement : l'avoir est rendu
        self.client.post(f"/api/orders/{r.data['id']}/cancel/")
        self.assertEqual(self.credit(), 100_000)

    def test_annulation_par_la_boutique_remboursee_en_avoir(self):
        self.give_credit(2_000_000)
        self.add(self.riz, 1)
        self.add(self.tel, 1)
        order = self.checkout("orange_money", use_credit=True).data  # entièrement payée avec l'avoir
        vo = VendorOrder.objects.get(order_id=order["id"], vendor=self.madina)
        self.client.force_authenticate(self.madina.user)
        self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": "cancelled"})
        self.client.force_authenticate(self.user)
        self.assertEqual(self.credit(), 2_000_000 - order["total_amount"] + vo.total_amount)
        data = self.client.get(f"/api/orders/{order['id']}/").data
        self.assertEqual((data["payment_status"], data["refunded_amount"]), ("paid", vo.total_amount))  # l'autre partie continue

    def test_avoir_rendu_sur_mobile_money(self):
        from .credit import process_cash_out
        from .models import CreditRefund

        self.give_credit(50_000)
        verified_account(self.user, phone="620000001")
        r = self.client.post("/api/wallet/credit/cash-out/", {"amount": 80_000})
        self.assertEqual(r.status_code, 400)
        r = self.client.post("/api/wallet/credit/cash-out/", {"amount": 50_000})
        self.assertEqual(r.status_code, 201, r.data)
        self.assertEqual(self.credit(), 0)  # réservé aussitôt
        admin = User.objects.create_superuser(email="adm@test.gn", password="x", name="Adm")
        process_cash_out(CreditRefund.objects.get(), admin, paid=False, note="Numéro invalide")
        self.assertEqual(self.credit(), 50_000)  # refusé : avoir rendu
