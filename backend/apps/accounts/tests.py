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
