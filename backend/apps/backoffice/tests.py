from contextlib import ExitStack
from unittest.mock import patch

import pyotp
from django.test import override_settings
from django.utils import timezone

from apps.accounts.models import User
from apps.catalog.models import Category, Review
from apps.core.models import AuditLog, SiteSettings
from apps.core.storage import PrivateStorage
from apps.core.tests_utils import GuineeMallTestCase, image_file
from apps.delivery.models import Rider
from apps.orders.models import Order, VendorOrder
from apps.vendors.models import Vendor, VerificationRequest
from apps.wallet.models import LedgerEntry, PayoutRequest


class BackofficeTestCase(GuineeMallTestCase):
    def setUp(self):
        super().setUp()
        self.admin = User.objects.create_superuser(email="chef@test.gn", password="x", name="Chef")
        self.admin.totp_secret = pyotp.random_base32()
        self.admin.totp_enabled_at = timezone.now()
        self.admin.save()
        self.client.force_authenticate(self.admin)

    def logged(self, action):
        return AuditLog.objects.filter(action=action, actor=self.admin).exists()


class AccessTests(GuineeMallTestCase):
    def test_reserve_a_l_equipe_avec_double_authentification(self):
        self.client.force_authenticate(self.make_client())
        self.assertEqual(self.client.get("/api/backoffice/orders/").status_code, 403)
        vendor = self.make_vendor()
        self.client.force_authenticate(vendor.user)
        self.assertEqual(self.client.get("/api/backoffice/vendors/").status_code, 403)
        admin_sans_2fa = User.objects.create_superuser(email="a@test.gn", password="x", name="A")
        self.client.force_authenticate(admin_sans_2fa)
        self.assertEqual(self.client.get("/api/backoffice/users/").status_code, 403)


class VendorTests(BackofficeTestCase):
    def test_approuver_suspendre_et_commission(self):
        vendor = self.make_vendor("Nouvelle", status=Vendor.Status.PENDING)
        r = self.client.post(f"/api/backoffice/vendors/{vendor.pk}/approve/")
        self.assertEqual(r.data["status"], "approved")
        self.assertTrue(vendor.user.notifications.filter(title__contains="en ligne").exists())
        self.client.post(f"/api/backoffice/vendors/{vendor.pk}/suspend/", {"reason": "Produits interdits"})
        vendor.refresh_from_db()
        self.assertEqual(vendor.status, "suspended")
        r = self.client.patch(f"/api/backoffice/vendors/{vendor.pk}/", {"is_premium": True, "commission_rate": "0.05"})
        self.assertEqual((r.data["is_premium"], r.data["commission_rate"]), (True, "0.050"))
        self.assertEqual(self.client.patch(f"/api/backoffice/vendors/{vendor.pk}/", {"commission_rate": "0.9"}).status_code, 400)
        # Le statut ne se change que par les actions (notification + journal)
        self.client.patch(f"/api/backoffice/vendors/{vendor.pk}/", {"status": "approved"})
        vendor.refresh_from_db()
        self.assertEqual(vendor.status, "suspended")
        self.assertTrue(self.logged("vendor.approved") and self.logged("vendor.suspended"))

    @override_settings(MEDIA_ROOT="/tmp/claude-1000/guineemall-test-media")
    def test_verification_documents_et_decision(self):
        vendor = self.make_vendor()
        private = PrivateStorage(location="/tmp/claude-1000/guineemall-test-private")
        with ExitStack() as stack:
            for f in ("document_front", "document_back", "shop_photo"):
                stack.enter_context(patch.object(VerificationRequest._meta.get_field(f), "storage", private))
            vr = VerificationRequest.objects.create(vendor=vendor, document_type="id_card", document_number="GN1",
                                                    document_front=image_file("cni.png"))
            listed = self.client.get("/api/backoffice/verifications/?status=pending").data["results"]
            self.assertEqual(listed[0]["documents"], ["document_front"])
            r = self.client.get(f"/api/backoffice/verifications/{vr.pk}/document/document_front/")
            self.assertEqual(r.status_code, 200)
            self.assertEqual(self.client.post(f"/api/backoffice/verifications/{vr.pk}/reject/").status_code, 400)  # motif obligatoire
            self.client.post(f"/api/backoffice/verifications/{vr.pk}/approve/")
        vendor.refresh_from_db()
        self.assertTrue(vendor.is_verified)
        self.assertTrue(self.logged("verification.document_viewed"))


class PeopleTests(BackofficeTestCase):
    def test_livreur_valide_puis_suspendu(self):
        user = User.objects.create_user(email="m@test.gn", password="x", name="Moto", role="livreur")
        rider = Rider.objects.create(user=user)
        self.assertTrue(self.client.post(f"/api/backoffice/riders/{rider.pk}/approve/").data["is_approved"])
        self.assertFalse(self.client.post(f"/api/backoffice/riders/{rider.pk}/suspend/").data["is_approved"])

    def test_desactiver_un_compte_et_pas_le_sien(self):
        client = self.make_client()
        r = self.client.post(f"/api/backoffice/users/{client.pk}/deactivate/")
        self.assertFalse(r.data["is_active"])
        self.assertEqual(self.client.post("/api/auth/token/", {"email": client.email, "password": "MotDePasse123!"}).status_code, 401)
        self.assertEqual(self.client.post(f"/api/backoffice/users/{self.admin.pk}/deactivate/").status_code, 400)

    def test_reinitialiser_la_double_authentification(self):
        collegue = User.objects.create_superuser(email="b@test.gn", password="x", name="B")
        collegue.totp_secret, collegue.totp_enabled_at = "X", timezone.now()
        collegue.save()
        self.assertFalse(self.client.post(f"/api/backoffice/users/{collegue.pk}/reset-2fa/").data["has_2fa"])
        self.assertEqual(self.client.post(f"/api/backoffice/users/{self.admin.pk}/reset-2fa/").status_code, 400)


class MoneyTests(BackofficeTestCase):
    def test_retrait_paye_avec_reference(self):
        vendor = self.make_vendor()
        LedgerEntry.objects.create(user=vendor.user, kind="adjustment", amount=100_000)
        payout = PayoutRequest.objects.create(user=vendor.user, amount=60_000, method="orange_money", phone="620000000")
        self.assertEqual(self.client.post(f"/api/backoffice/payouts/{payout.pk}/pay/").status_code, 400)
        r = self.client.post(f"/api/backoffice/payouts/{payout.pk}/pay/", {"reference": "OM-778899"})
        self.assertEqual(r.data["status"], "paid")
        self.assertEqual(LedgerEntry.balance(vendor.user), 40_000)

    def test_remboursement_et_deblocage_du_code(self):
        user = self.make_client()
        vendor = self.make_vendor()
        order = Order.objects.create(user=user, total_amount=1000, delivery_city="Conakry", delivery_landmark="x",
                                     delivery_phone="620000000", payment_method="orange_money", payment_status="refund_pending")
        vo = VendorOrder.objects.create(order=order, vendor=vendor, total_amount=1000, status="ready", delivery_code_attempts=5)
        detail = self.client.get(f"/api/backoffice/orders/{order.pk}/").data
        self.assertTrue(detail["vendor_orders"][0]["code_blocked"])
        self.client.post(f"/api/backoffice/orders/{order.pk}/unlock-code/{vo.pk}/")
        vo.refresh_from_db()
        self.assertEqual(vo.delivery_code_attempts, 0)
        r = self.client.post(f"/api/backoffice/orders/{order.pk}/refunded/", {"reference": "OM-REMB-1"})
        self.assertEqual(r.data["payment_status"], "refunded")
        self.assertTrue(self.logged("order.refunded"))


class CatalogTests(BackofficeTestCase):
    def test_categories_et_moderation(self):
        r = self.client.post("/api/backoffice/categories/", {"name": "Électroménager", "status": "active"})
        self.assertEqual((r.status_code, r.data["slug"]), (201, "electromenager"))
        vendor = self.make_vendor()
        product = self.make_product(vendor)
        cat = product.category
        self.assertEqual(self.client.delete(f"/api/backoffice/categories/{cat.pk}/").status_code, 400)  # contient des produits

        self.client.patch(f"/api/backoffice/products/{product.pk}/", {"status": "inactive"})
        self.client.logout()
        self.assertEqual(self.client.get("/api/products/").data["count"], 0)  # masqué au public

        self.client.force_authenticate(self.admin)
        review = Review.objects.create(product=product, user=self.make_client(), rating=1, comment="Insulte")
        self.client.patch(f"/api/backoffice/reviews/{review.pk}/", {"is_visible": False})
        review.refresh_from_db()
        self.assertFalse(review.is_visible)
        self.assertTrue(self.logged("category.created") and self.logged("product.inactive") and self.logged("review.hidden"))


class SettingsTests(BackofficeTestCase):
    def test_reglages_zones_codes_et_journal(self):
        r = self.client.patch("/api/backoffice/settings/", {"announcement": "Bienvenue !", "enable_card": False}, format="json")
        self.assertEqual(r.data["announcement"], "Bienvenue !")
        self.assertFalse(SiteSettings.load().enable_card)
        self.assertNotIn("card", [m["value"] for m in self.client.get("/api/config/").data["payment_methods"]])

        zone = self.client.post("/api/backoffice/zones/", {"city": "Kindia", "commune": "", "fee": 50000, "delay": "48 h", "is_active": True}, format="json").data
        self.client.patch(f"/api/backoffice/zones/{zone['id']}/", {"fee": 55000})
        self.assertIn("Kindia", [l["city"] for l in self.client.get("/api/config/").data["delivery_locations"]])

        r = self.client.post("/api/backoffice/coupons/", {"code": "tabaski", "type": "percent", "value": 15})
        self.assertEqual(r.data["code"], "TABASKI")
        actions = [a["action"] for a in self.client.get("/api/backoffice/audit/").data["results"]]
        self.assertTrue({"site_settings.changed", "delivery_zone.created", "delivery_zone.updated", "coupon.created"} <= set(actions))
