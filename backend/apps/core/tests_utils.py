from io import BytesIO

from django.core.cache import cache
from django.core.files.uploadedfile import SimpleUploadedFile
from PIL import Image
from rest_framework.test import APITestCase

from apps.accounts.models import Address, User
from apps.catalog.models import Category, Product
from apps.delivery.models import DeliveryZone
from apps.vendors.models import Vendor

DELIVERY_FEE = 20_000


def image_file(name="photo.png"):
    """Vraie petite image PNG pour les champs ImageField."""
    buffer = BytesIO()
    Image.new("RGB", (4, 4), "orange").save(buffer, format="PNG")
    return SimpleUploadedFile(name, buffer.getvalue(), content_type="image/png")


class GuineeMallTestCase(APITestCase):
    """Fabriques de données communes aux tests de l'API."""

    @classmethod
    def setUpTestData(cls):
        DeliveryZone.objects.create(city="Conakry", commune="Ratoma", fee=DELIVERY_FEE)

    def setUp(self):
        super().setUp()
        cache.clear()  # compteurs de limitation des tentatives

    def make_client(self, email="client@test.gn"):
        return User.objects.create_user(email=email, password="MotDePasse123!", name="Client Test", phone="620000001")

    def make_vendor(self, shop_name="Boutique Kipé", status=Vendor.Status.APPROVED, email=None):
        email = email or f"{shop_name.lower().replace(' ', '')}@test.gn"
        user = User.objects.create_user(email=email, password="x", name=shop_name, role=User.Role.VENDEUR)
        return Vendor.objects.create(
            user=user, shop_name=shop_name, status=status, pickup_city="Conakry", pickup_commune="Ratoma",
            pickup_quartier="Kipé", pickup_landmark="Face à la pharmacie", whatsapp="622000000",
        )

    def make_product(self, vendor, name="Téléphone", price=1_500_000, stock=10, category=None, **kwargs):
        category = category or Category.objects.get_or_create(name="Électronique")[0]
        return Product.objects.create(
            vendor=vendor, category=category, name=name, description="…", price=price, stock=stock, **kwargs
        )

    def make_address(self, user):
        return Address.objects.create(
            user=user, commune="Ratoma", quartier="Kipé", landmark="Derrière la mosquée", contact_phone="620000000"
        )
