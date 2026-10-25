from apps.core.tests_utils import GuineeMallTestCase
from apps.vendors.models import Vendor


class AuthTests(GuineeMallTestCase):
    def test_inscription_client_puis_connexion(self):
        r = self.client.post("/api/auth/register/", {
            "email": "awa@test.gn", "name": "Awa Camara", "phone": "620112233", "password": "MotDePasse123!",
        })
        self.assertEqual(r.status_code, 201, r.data)
        self.assertIn("access", r.data["tokens"])

        r = self.client.post("/api/auth/token/", {"email": "awa@test.gn", "password": "MotDePasse123!"})
        self.assertEqual(r.status_code, 200)
        self.client.credentials(HTTP_AUTHORIZATION=f"Bearer {r.data['access']}")
        me = self.client.get("/api/auth/me/").data
        self.assertEqual((me["name"], me["role"], me["shop"]), ("Awa Camara", "client", None))

    def test_inscription_vendeur_cree_une_boutique_en_attente(self):
        r = self.client.post("/api/auth/register/vendor/", {
            "email": "mamadou@test.gn", "name": "Mamadou Diallo", "password": "MotDePasse123!",
            "shop_name": "Chez Mamadou", "whatsapp": "620445566",
        })
        self.assertEqual(r.status_code, 201, r.data)
        vendor = Vendor.objects.get(user__email="mamadou@test.gn")
        self.assertEqual((vendor.status, vendor.user.role), ("pending", "vendeur"))

    def test_on_ne_peut_pas_se_donner_le_role_admin(self):
        self.client.post("/api/auth/register/", {
            "email": "x@test.gn", "name": "X", "password": "MotDePasse123!", "role": "admin",
        })
        user = self.client.post("/api/auth/token/", {"email": "x@test.gn", "password": "MotDePasse123!"})
        self.client.credentials(HTTP_AUTHORIZATION=f"Bearer {user.data['access']}")
        self.client.patch("/api/auth/me/", {"role": "admin"})
        self.assertEqual(self.client.get("/api/auth/me/").data["role"], "client")

    def test_une_seule_adresse_par_defaut(self):
        user = self.make_client()
        self.client.force_authenticate(user)
        data = {"landmark": "Carrefour Bambeto", "contact_phone": "620000000", "is_default": True}
        self.client.post("/api/auth/addresses/", data)
        self.client.post("/api/auth/addresses/", {**data, "landmark": "Marché Madina"})
        self.assertEqual(user.addresses.filter(is_default=True).count(), 1)


class PasswordTests(GuineeMallTestCase):
    def test_mot_de_passe_oublie(self):
        import re

        from django.core import mail

        self.make_client("oubli@test.gn")
        self.client.post("/api/auth/password/reset/", {"email": "OUBLI@test.gn"})
        self.client.post("/api/auth/password/reset/", {"email": "inconnu@test.gn"})  # même réponse, pas d'email
        self.assertEqual(len(mail.outbox), 1)
        uid, token = re.search(r"uid=([\w-]+)&token=([\w-]+)", mail.outbox[0].body).groups()

        r = self.client.post("/api/auth/password/reset/confirm/", {"uid": uid, "token": token, "password": "NouveauMdp2026!"})
        self.assertEqual(r.status_code, 204)
        r = self.client.post("/api/auth/password/reset/confirm/", {"uid": uid, "token": token, "password": "Autre2026!!"})
        self.assertEqual(r.status_code, 400)  # lien à usage unique
        self.assertEqual(self.client.post("/api/auth/token/", {"email": "oubli@test.gn", "password": "NouveauMdp2026!"}).status_code, 200)

    def test_changer_son_mot_de_passe(self):
        user = self.make_client()
        self.client.force_authenticate(user)
        bad = self.client.post("/api/auth/password/change/", {"current_password": "faux", "new_password": "Nouveau2026!!"})
        self.assertEqual(bad.status_code, 400)
        ok = self.client.post("/api/auth/password/change/", {"current_password": "MotDePasse123!", "new_password": "Nouveau2026!!"})
        self.assertEqual(ok.status_code, 200)
        self.assertIn("refresh", ok.data)


class ConfigTests(GuineeMallTestCase):
    def test_modes_de_paiement_pilotes_par_l_admin(self):
        from apps.core.models import SiteSettings

        site = SiteSettings.load()
        site.enable_card = False
        site.save()
        config = self.client.get("/api/config/").data
        self.assertNotIn("card", [m["value"] for m in config["payment_methods"]])
        self.assertEqual(config["delivery_locations"], [{"city": "Conakry", "communes": ["Ratoma"]}])
        self.assertEqual(config["choices"]["vendor_order_status"][2]["action"], "Accepter la commande")

        vendor = self.make_vendor()
        product = self.make_product(vendor)
        user = self.make_client()
        self.client.force_authenticate(user)
        self.client.post("/api/cart/", {"product_slug": product.slug, "quantity": 1})
        r = self.client.post("/api/orders/", {"address": self.make_address(user).pk, "payment_method": "card"})
        self.assertEqual(r.status_code, 400)


class PhoneTests(GuineeMallTestCase):
    def test_numero_nettoye_et_limite_a_9_chiffres(self):
        user = self.make_client()
        self.client.force_authenticate(user)
        for saisie in ["620 11 22 33", "+224 620-11-22-33", "00224620112233"]:
            r = self.client.patch("/api/auth/me/", {"phone": saisie})
            self.assertEqual((r.status_code, r.data["phone"]), (200, "620112233"), saisie)
        for invalide in ["62011223", "6201122334", "abc"]:
            r = self.client.patch("/api/auth/me/", {"phone": invalide})
            self.assertEqual(r.status_code, 400, invalide)
            self.assertIn("9 chiffres", str(r.data["phone"][0]))

    def test_adresse_avec_numero_invalide_refusee(self):
        self.client.force_authenticate(self.make_client())
        r = self.client.post("/api/auth/addresses/", {"landmark": "Marché", "contact_phone": "62000000000"})
        self.assertEqual(r.status_code, 400)


class SessionTests(GuineeMallTestCase):
    def login(self):
        return self.client.post("/api/auth/token/", {"email": "s@test.gn", "password": "MotDePasse123!"}).data

    def test_deconnexion_revoque_le_jeton(self):
        self.make_client("s@test.gn")
        tokens = self.login()
        self.client.post("/api/auth/logout/", {"refresh": tokens["refresh"]})
        r = self.client.post("/api/auth/token/refresh/", {"refresh": tokens["refresh"]})
        self.assertEqual(r.status_code, 401)

    def test_changer_de_mot_de_passe_deconnecte_les_autres_appareils(self):
        user = self.make_client("s@test.gn")
        telephone_vole = self.login()
        self.client.force_authenticate(user)
        self.client.post("/api/auth/password/change/", {"current_password": "MotDePasse123!", "new_password": "Nouveau2026!!"})
        self.client.force_authenticate(None)
        r = self.client.post("/api/auth/token/refresh/", {"refresh": telephone_vole["refresh"]})
        self.assertEqual(r.status_code, 401)

    def test_ancien_jeton_de_renouvellement_inutilisable_apres_rotation(self):
        self.make_client("s@test.gn")
        tokens = self.login()
        self.assertEqual(self.client.post("/api/auth/token/refresh/", {"refresh": tokens["refresh"]}).status_code, 200)
        self.assertEqual(self.client.post("/api/auth/token/refresh/", {"refresh": tokens["refresh"]}).status_code, 401)
