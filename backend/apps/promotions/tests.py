from datetime import timedelta

from django.utils import timezone

from apps.core.tests_utils import DELIVERY_FEE
from apps.orders.models import VendorOrder
from apps.orders.tests import OrderTestCase

from .models import Campaign, Coupon


class CouponTests(OrderTestCase):
    def quote(self, code):
        return self.client.post("/api/orders/quote/", {"address": self.address.pk, "coupon_code": code})

    def test_code_plateforme_reparti_entre_boutiques(self):
        Coupon.objects.create(code="TABASKI", value=10)
        self.add(self.tel, 1)  # 1 500 000
        self.add(self.riz, 1)  # 350 000
        q = self.quote("tabaski").data  # insensible à la casse
        self.assertEqual(q["discount"], 185_000)
        self.assertEqual(q["total"], 1_850_000 - 185_000 + 2 * DELIVERY_FEE)

        order = self.checkout(coupon_code="TABASKI").data
        self.assertEqual(order["discount_amount"], 185_000)
        shares = {vo.vendor.shop_name: vo.platform_discount for vo in VendorOrder.objects.all()}
        self.assertEqual(shares, {"Kipé": 150_000, "Madina": 35_000})

    def test_code_boutique_uniquement_sur_ses_articles(self):
        Coupon.objects.create(code="MADINA5K", type="fixed", value=5_000, vendor=self.madina)
        self.add(self.tel, 1)
        self.add(self.riz, 1)
        self.assertEqual(self.quote("MADINA5K").data["discount"], 5_000)
        self.checkout(coupon_code="MADINA5K")
        self.assertEqual(VendorOrder.objects.get(vendor=self.madina).vendor_discount, 5_000)

    def test_codes_invalides(self):
        self.add(self.riz, 1)
        Coupon.objects.create(code="VIEUX", value=10, ends_at=timezone.now() - timedelta(days=1))
        Coupon.objects.create(code="GROS", value=10, min_amount=1_000_000)
        Coupon.objects.create(code="KIPE", value=10, vendor=self.kipe)
        self.assertIn("expiré", self.quote("VIEUX").data["coupon_error"])
        self.assertIn("minimum", self.quote("GROS").data["coupon_error"])
        self.assertIn("aucun article", self.quote("KIPE").data["coupon_error"])
        self.assertEqual(self.checkout(coupon_code="VIEUX").status_code, 400)

    def test_code_utilisable_une_fois_par_client(self):
        Coupon.objects.create(code="BIENVENUE", value=10)
        self.add(self.riz, 1)
        self.assertEqual(self.checkout(coupon_code="BIENVENUE").status_code, 201)
        self.add(self.riz, 1)
        self.assertEqual(self.checkout(coupon_code="BIENVENUE").status_code, 400)

    def test_vendeur_cree_ses_codes(self):
        self.client.force_authenticate(self.kipe.user)
        r = self.client.post("/api/vendor/coupons/", {"code": "kipe20", "type": "percent", "value": 20})
        self.assertEqual((r.status_code, r.data["code"]), (201, "KIPE20"))
        self.assertEqual(Coupon.objects.get(code="KIPE20").vendor, self.kipe)

    def test_operation_commerciale_en_cours(self):
        now = timezone.now()
        c = Campaign.objects.create(name="Tabaski 2026", starts_at=now - timedelta(days=1), ends_at=now + timedelta(days=5))
        c.products.add(self.riz)
        Campaign.objects.create(name="Passée", starts_at=now - timedelta(days=9), ends_at=now - timedelta(days=2))
        self.assertEqual([x["slug"] for x in self.client.get("/api/campaigns/").data], ["tabaski-2026"])
        detail = self.client.get("/api/campaigns/tabaski-2026/").data
        self.assertEqual([p["name"] for p in detail["products"]], ["Riz"])


class ReferralTests(OrderTestCase):
    def test_parrainage_complet(self):
        parrain = self.user
        self.client.logout()
        r = self.client.post("/api/auth/register/", {
            "email": "filleul@test.gn", "name": "Filleul", "password": "MotDePasse123!", "referral": parrain.referral_code,
        })
        self.assertEqual(r.status_code, 201)
        filleul = parrain.referrals.get()
        bon = filleul.personal_coupons.get()
        self.assertEqual(bon.value, 10_000)

        # Le bon est personnel : le parrain ne peut pas l'utiliser
        self.client.force_authenticate(parrain)
        self.add(self.tel, 1)
        r = self.client.post("/api/orders/quote/", {"address": self.address.pk, "coupon_code": bon.code})
        self.assertIn("personnel", r.data["coupon_error"])

        # Première commande livrée du filleul → bon pour le parrain
        self.client.force_authenticate(filleul)
        address = self.make_address(filleul)
        self.client.post("/api/cart/", {"product_slug": self.riz.slug, "quantity": 1})
        self.client.post("/api/orders/", {"address": address.pk, "payment_method": "cash_on_delivery"})
        vo = VendorOrder.objects.get(order__user=filleul)
        self.client.force_authenticate(self.madina.user)
        for s in ["confirmed", "preparing", "ready", "delivered"]:
            with self.captureOnCommitCallbacks(execute=True):
                self.client.post(f"/api/vendor/orders/{vo.pk}/status/", {"status": s})
        self.assertEqual(parrain.personal_coupons.count(), 1)

    def test_code_parrain_inconnu(self):
        r = self.client.post("/api/auth/register/", {"email": "x@test.gn", "name": "X", "password": "MotDePasse123!", "referral": "NOPE"})
        self.assertEqual(r.status_code, 400)
