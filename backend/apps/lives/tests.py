from django.utils import timezone

from apps.catalog.models import Favorite
from apps.core.tests_utils import GuineeMallTestCase


class LiveTests(GuineeMallTestCase):
    def test_direct_complet(self):
        vendor = self.make_vendor("Kipé")
        product = self.make_product(vendor)
        fan = self.make_client()
        Favorite.objects.create(user=fan, product=product)

        self.client.force_authenticate(vendor.user)
        live = self.client.post("/api/vendor/lives/", {
            "title": "Arrivage téléphones", "stream_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
            "starts_at": timezone.now().isoformat(), "product_slugs": [product.slug],
        }, format="json").data
        self.client.post(f"/api/vendor/lives/{live['id']}/start/")
        self.client.post(f"/api/vendor/lives/{live['id']}/pin/", {"product": product.slug})
        self.assertTrue(fan.notifications.filter(title__contains="en direct").exists())

        self.client.logout()
        detail = self.client.get(f"/api/lives/{live['id']}/").data
        self.assertEqual(detail["status"], "live")
        self.assertEqual(detail["embed_url"], "https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1")
        self.assertEqual(detail["pinned_product"], product.slug)
        self.assertEqual([p["slug"] for p in detail["products"]], [product.slug])

    def test_produit_d_une_autre_boutique_refuse(self):
        vendor = self.make_vendor("Kipé")
        autre = self.make_product(self.make_vendor("Autre"), name="Pas à moi")
        self.client.force_authenticate(vendor.user)
        r = self.client.post("/api/vendor/lives/", {
            "title": "x", "stream_url": "https://youtu.be/dQw4w9WgXcQ", "starts_at": timezone.now().isoformat(),
            "product_slugs": [autre.slug],
        }, format="json")
        self.assertEqual(r.status_code, 400)
