from django.test import override_settings

from apps.core.tests_utils import GuineeMallTestCase, image_file


@override_settings(MEDIA_ROOT="/tmp/claude-1000/guineemall-test-media")
class VerificationTests(GuineeMallTestCase):
    def test_demande_de_badge(self):
        vendor = self.make_vendor()
        self.client.force_authenticate(vendor.user)
        data = {
            "document_type": "id_card", "document_number": "GN123",
            "document_front": image_file("cni.png"),
        }
        r = self.client.post("/api/vendor/verification/", data, format="multipart")
        self.assertEqual(r.status_code, 201, r.data)
        self.assertNotIn("document_front", r.data)  # pièce d'identité jamais renvoyée
        data["document_front"] = image_file("cni.png")
        self.assertEqual(self.client.post("/api/vendor/verification/", data, format="multipart").status_code, 400)
