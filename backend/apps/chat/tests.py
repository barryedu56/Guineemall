from django.core.files.uploadedfile import SimpleUploadedFile
from django.test import override_settings

from apps.core.tests_utils import GuineeMallTestCase

from .models import Conversation


@override_settings(MEDIA_ROOT="/tmp/claude-1000/guineemall-test-media")
class ChatTests(GuineeMallTestCase):
    def setUp(self):
        super().setUp()
        self.vendor = self.make_vendor("Kipé")
        self.product = self.make_product(self.vendor)
        self.user = self.make_client()
        self.client.force_authenticate(self.user)

    def start(self):
        return self.client.post("/api/conversations/start/", {"shop": self.vendor.slug, "product": self.product.slug})

    def test_conversation_depuis_un_produit(self):
        conv = self.start().data
        self.start()  # pas de doublon
        self.assertEqual(Conversation.objects.count(), 1)
        messages = self.client.get(f"/api/conversations/{conv['id']}/messages/").data["results"]
        self.assertEqual(len(messages), 1)
        self.assertEqual(messages[0]["product"]["slug"], self.product.slug)

    def test_echange_non_lus_et_lecture(self):
        conv_id = self.start().data["id"]
        self.client.force_authenticate(self.vendor.user)
        convs = self.client.get("/api/conversations/").data
        self.assertEqual((convs[0]["other"]["name"], convs[0]["unread"]), ("Client Test", 1))
        self.client.post(f"/api/conversations/{conv_id}/messages/", {"content": "Bonjour, il est disponible !"})
        self.client.post(f"/api/conversations/{conv_id}/read/")
        self.assertEqual(self.client.get("/api/conversations/").data[0]["unread"], 0)

        self.client.force_authenticate(self.user)
        conv = self.client.get("/api/conversations/").data[0]
        self.assertEqual((conv["unread"], conv["other"]["kind"]), (1, "shop"))
        self.assertTrue(self.user.notifications.filter(kind="message").exists())

    def test_message_vocal(self):
        conv_id = self.start().data["id"]
        audio = SimpleUploadedFile("vocal.webm", b"\x1aE\xdf\xa3", content_type="audio/webm")
        r = self.client.post(f"/api/conversations/{conv_id}/messages/", {"attachment": audio}, format="multipart")
        self.assertEqual((r.status_code, r.data["type"]), (201, "voice"))

    def test_intrus_sans_acces(self):
        conv_id = self.start().data["id"]
        self.client.force_authenticate(self.make_client("intrus@test.gn"))
        self.assertEqual(self.client.get(f"/api/conversations/{conv_id}/messages/").status_code, 404)

    def test_message_vide_refuse(self):
        conv_id = self.start().data["id"]
        self.assertEqual(self.client.post(f"/api/conversations/{conv_id}/messages/", {"content": "  "}).status_code, 400)
