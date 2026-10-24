from django.test import TestCase

from apps.accounts.models import User
from apps.core.tests_utils import GuineeMallTestCase

from .services import notify
from .whatsapp import normalize_phone


class WhatsAppTests(TestCase):
    def test_numeros_guineens(self):
        self.assertEqual(normalize_phone("620 11 22 33"), "224620112233")
        self.assertEqual(normalize_phone("+224 620112233"), "224620112233")
        self.assertEqual(normalize_phone("00224620112233"), "224620112233")
        self.assertEqual(normalize_phone(""), "")


class NotificationApiTests(GuineeMallTestCase):
    def test_liste_et_lecture(self):
        user = self.make_client()
        notify(user, "order", "Test", "Corps")
        self.client.force_authenticate(user)
        self.assertEqual(self.client.get("/api/notifications/").data["unread"], 1)
        self.client.post("/api/notifications/read_all/")
        self.assertEqual(self.client.get("/api/notifications/").data["unread"], 0)
