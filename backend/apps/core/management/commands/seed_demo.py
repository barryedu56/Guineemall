"""
Données de démonstration pour le développement local :
    python manage.py seed_demo

Crée des catégories, 3 boutiques approuvées, des produits avec images
et des statuts, plus un client de test. Relancer la commande ne crée pas de doublons.
"""

from io import BytesIO

from django.core.files.base import ContentFile
from django.core.management.base import BaseCommand
from django.db import transaction
from PIL import Image, ImageDraw, ImageFont

from datetime import timedelta

from django.utils import timezone

from apps.accounts.models import Address, User
from apps.catalog.models import Category, Product, ProductImage
from apps.delivery.models import DeliveryZone, PickupPoint, Rider
from apps.promotions.models import Campaign, Coupon
from apps.statuts.models import Statut
from apps.vendors.models import Vendor

# Tarifs indicatifs à ajuster dans l'admin (Livraison › Zones de livraison)
DELIVERY_ZONES = [
    ("Conakry", "Kaloum", 25_000), ("Conakry", "Dixinn", 20_000), ("Conakry", "Matam", 20_000),
    ("Conakry", "Ratoma", 15_000), ("Conakry", "Matoto", 20_000), ("Conakry", "Sonfonia", 25_000),
    ("Conakry", "Gbessia", 20_000), ("Conakry", "Tombolia", 30_000), ("Conakry", "Lambanyi", 25_000),
    ("Conakry", "", 30_000), ("Coyah", "", 40_000), ("Dubréka", "", 40_000), ("Kindia", "", 60_000),
    ("Boké", "", 80_000), ("Mamou", "", 80_000), ("Labé", "", 100_000), ("Kankan", "", 120_000),
    ("Faranah", "", 100_000), ("N'Zérékoré", "", 130_000),
]

PICKUP_POINTS = [
    ("Relais Bambeto", "Ratoma", "Carrefour Bambeto, à côté de la pharmacie", "620101010", 5_000),
    ("Relais Madina", "Matam", "Marché Madina, entrée principale", "620202020", 5_000),
    ("Relais Kaloum", "Kaloum", "Avenue de la République, immeuble Sonoco", "620303030", 7_000),
]

DEMO_PASSWORD = "demo1234"

CATEGORIES = ["Téléphones", "Mode", "Alimentation", "Maison", "Beauté"]

SHOPS = [
    {
        "email": "kipe.tech@demo.gn", "name": "Mamadou Diallo", "shop_name": "Kipé Tech",
        "description": "Téléphones et accessoires neufs, garantie 6 mois. Kipé, Ratoma.",
        "whatsapp": "620112233", "verified": True, "color": (255, 193, 7),
        "products": [
            ("Téléphones", "Samsung Galaxy A15 128 Go", 1_850_000, 2_100_000, 8),
            ("Téléphones", "Tecno Spark 20 Pro", 1_450_000, None, 12),
            ("Téléphones", "Écouteurs Bluetooth sans fil", 150_000, 200_000, 30),
            ("Téléphones", "Chargeur rapide 25W", 85_000, None, 0),
            ("Maison", "Ventilateur rechargeable solaire", 450_000, None, 6),
        ],
    },
    {
        "email": "madina.mode@demo.gn", "name": "Fatoumata Camara", "shop_name": "Madina Mode",
        "description": "Pagnes, bazin et prêt-à-porter. Marché Madina.",
        "whatsapp": "621445566", "verified": False, "color": (239, 108, 0),
        "products": [
            ("Mode", "Bazin riche brodé (5 m)", 650_000, 800_000, 15),
            ("Mode", "Pagne wax hollandais (6 yards)", 280_000, None, 25),
            ("Mode", "Boubou homme brodé", 520_000, None, 4),
            ("Beauté", "Beurre de karité pur 500 g", 45_000, None, 40),
            ("Beauté", "Savon noir naturel", 15_000, None, 100),
        ],
    },
    {
        "email": "fouta.saveurs@demo.gn", "name": "Alpha Bah", "shop_name": "Fouta Saveurs",
        "description": "Produits du Fouta Djallon livrés à Conakry : fonio, miel, café.",
        "whatsapp": "622778899", "verified": True, "color": (76, 175, 80),
        "products": [
            ("Alimentation", "Fonio précuit 1 kg", 35_000, None, 60),
            ("Alimentation", "Miel du Fouta 1 L", 120_000, 150_000, 20),
            ("Alimentation", "Café de Ziama moulu 500 g", 75_000, None, 18),
            ("Alimentation", "Riz local étuvé 25 kg", 380_000, None, 10),
            ("Maison", "Panier tressé artisanal", 95_000, None, 7),
        ],
    },
]

STATUTS = {
    "Kipé Tech": [("Arrivage Samsung A15 ! Garantie 6 mois 📱", "Samsung Galaxy A15 128 Go")],
    "Fouta Saveurs": [
        ("Miel du Fouta en promo cette semaine 🍯", "Miel du Fouta 1 L"),
        ("Livraison gratuite à Ratoma ce week-end", None),
    ],
}


def placeholder(text, color, size=(800, 800)):
    """Image unie avec le nom du produit, en attendant de vraies photos."""
    img = Image.new("RGB", size, color)
    draw = ImageDraw.Draw(img)
    try:
        font = ImageFont.truetype("DejaVuSans-Bold.ttf", 44)
    except OSError:
        font = ImageFont.load_default()
    words, lines, line = text.split(), [], ""
    for word in words:
        if draw.textlength(f"{line} {word}", font=font) > size[0] - 120:
            lines.append(line)
            line = word
        else:
            line = f"{line} {word}".strip()
    lines.append(line)
    y = size[1] // 2 - len(lines) * 30
    for line in lines:
        w = draw.textlength(line, font=font)
        draw.text(((size[0] - w) / 2, y), line, fill="white", font=font, stroke_width=2, stroke_fill=(0, 0, 0))
        y += 60
    buffer = BytesIO()
    img.save(buffer, format="JPEG", quality=80)
    return ContentFile(buffer.getvalue())


class Command(BaseCommand):
    help = "Crée des données de démonstration (boutiques, produits, statuts)."

    @transaction.atomic
    def handle(self, *args, **options):
        categories = {name: Category.objects.get_or_create(name=name)[0] for name in CATEGORIES}

        for shop in SHOPS:
            user, created = User.objects.get_or_create(
                email=shop["email"], defaults={"name": shop["name"], "role": User.Role.VENDEUR}
            )
            if created:
                user.set_password(DEMO_PASSWORD)
                user.save()
            vendor, _ = Vendor.objects.get_or_create(
                user=user,
                defaults={
                    "shop_name": shop["shop_name"], "description": shop["description"],
                    "whatsapp": shop["whatsapp"], "status": Vendor.Status.APPROVED,
                    "is_verified": shop["verified"],
                },
            )
            for category, name, price, compare, stock in shop["products"]:
                product, created = Product.objects.get_or_create(
                    vendor=vendor, name=name,
                    defaults={
                        "category": categories[category], "price": price, "compare_at_price": compare,
                        "stock": stock, "description": f"{name}, vendu par {shop['shop_name']}.",
                    },
                )
                if created:
                    image = ProductImage(product=product)
                    image.image.save(f"{product.slug}.jpg", placeholder(name, shop["color"]), save=True)

            for caption, product_name in STATUTS.get(shop["shop_name"], []):
                if vendor.statuts.active().filter(caption=caption).exists():
                    continue
                statut = Statut(
                    vendor=vendor, caption=caption,
                    product=vendor.products.filter(name=product_name).first() if product_name else None,
                )
                statut.media.save("statut.jpg", placeholder(caption, shop["color"], size=(720, 1280)), save=True)

        for city, commune, fee in DELIVERY_ZONES:
            DeliveryZone.objects.get_or_create(city=city, commune=commune, defaults={"fee": fee})
        for name, commune, address, phone, fee in PICKUP_POINTS:
            PickupPoint.objects.get_or_create(
                name=name, defaults={"city": "Conakry", "commune": commune, "address": address, "phone": phone, "fee": fee}
            )

        coupon, _ = Coupon.objects.get_or_create(
            code="BIENVENUE", defaults={"value": 10, "max_discount": 50_000, "description": "10 % sur la 1re commande"}
        )
        if not Campaign.objects.filter(name="Spécial Tabaski").exists():
            now = timezone.now()
            campaign = Campaign.objects.create(
                name="Spécial Tabaski", description="Bazin, boubous et produits du terroir pour la fête.",
                starts_at=now - timedelta(days=1), ends_at=now + timedelta(days=14), coupon=coupon, color="#EF6C00",
            )
            campaign.products.set(Product.objects.filter(category__name__in=["Mode", "Alimentation"]))

        rider_user, created = User.objects.get_or_create(
            email="livreur@demo.gn", defaults={"name": "Ibrahima Kouyaté", "role": User.Role.LIVREUR, "phone": "624000000"}
        )
        if created:
            rider_user.set_password(DEMO_PASSWORD)
            rider_user.save()
            Rider.objects.create(user=rider_user, communes="Ratoma, Dixinn, Matam", vehicle_plate="RC-1234-A", is_approved=True)

        client, created = User.objects.get_or_create(email="client@demo.gn", defaults={"name": "Aïssatou Sow"})
        if created:
            client.set_password(DEMO_PASSWORD)
            client.phone = "623000000"
            client.save()
            Address.objects.create(
                user=client, commune="Ratoma", quartier="Kipé", is_default=True,
                landmark="Derrière la station Total, portail vert", contact_phone="623000000",
            )

        self.stdout.write(self.style.SUCCESS("Données de démonstration prêtes."))
        self.stdout.write(f"  Client  : client@demo.gn / {DEMO_PASSWORD}  (code promo : BIENVENUE)")
        self.stdout.write(f"  Livreur : livreur@demo.gn / {DEMO_PASSWORD}")
        for shop in SHOPS:
            self.stdout.write(f"  Vendeur : {shop['email']} / {DEMO_PASSWORD}  ({shop['shop_name']})")
