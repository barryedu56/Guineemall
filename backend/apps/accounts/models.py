import secrets

from django.contrib.auth.models import AbstractUser, BaseUserManager
from django.db import models

from apps.core.models import TimeStampedModel


def generate_referral_code():
    alphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"  # sans 0/O ni 1/I, faciles à confondre
    while True:
        code = "".join(secrets.choice(alphabet) for _ in range(7))
        if not User.objects.filter(referral_code=code).exists():
            return code


class UserManager(BaseUserManager):
    """Connexion par email (comme l'ancien GuinéeMall), pas par nom d'utilisateur."""

    use_in_migrations = True

    def _create_user(self, email, password, **extra_fields):
        if not email:
            raise ValueError("L'adresse email est obligatoire.")
        email = self.normalize_email(email)
        user = self.model(email=email, **extra_fields)
        user.set_password(password)
        user.save(using=self._db)
        return user

    def create_user(self, email, password=None, **extra_fields):
        extra_fields.setdefault("is_staff", False)
        extra_fields.setdefault("is_superuser", False)
        return self._create_user(email, password, **extra_fields)

    def create_superuser(self, email, password=None, **extra_fields):
        extra_fields.setdefault("is_staff", True)
        extra_fields.setdefault("is_superuser", True)
        extra_fields.setdefault("role", User.Role.ADMIN)
        return self._create_user(email, password, **extra_fields)


class User(AbstractUser):
    class Role(models.TextChoices):
        ADMIN = "admin", "Administrateur"
        VENDEUR = "vendeur", "Vendeur"
        CLIENT = "client", "Client"
        LIVREUR = "livreur", "Livreur"

    username = None
    first_name = None
    last_name = None

    name = models.CharField("nom complet", max_length=255)
    email = models.EmailField("email", unique=True)
    phone = models.CharField("téléphone", max_length=20, blank=True)
    role = models.CharField(max_length=10, choices=Role.choices, default=Role.CLIENT)
    avatar = models.ImageField(upload_to="avatars/", blank=True)
    # Parrainage et affiliation : le même code sert aux deux
    referral_code = models.CharField("code parrain", max_length=12, unique=True, blank=True)
    referred_by = models.ForeignKey(
        "self", on_delete=models.SET_NULL, null=True, blank=True, related_name="referrals"
    )

    USERNAME_FIELD = "email"
    REQUIRED_FIELDS = ["name"]

    objects = UserManager()

    class Meta:
        verbose_name = "utilisateur"

    def __str__(self):
        return f"{self.name} <{self.email}>"

    def save(self, *args, **kwargs):
        if not self.referral_code:
            self.referral_code = generate_referral_code()
        super().save(*args, **kwargs)

    @property
    def is_vendeur(self):
        return self.role == self.Role.VENDEUR

    @property
    def is_client(self):
        return self.role == self.Role.CLIENT

    @property
    def is_admin(self):
        return self.role == self.Role.ADMIN


class Address(TimeStampedModel):
    """
    Adresse de livraison adaptée à la Guinée : beaucoup de lieux n'ont pas
    d'adresse postale, on s'appuie donc sur un repère + une position GPS.
    Remplace les champs texte delivery_address / address de l'ancien users.
    """

    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name="addresses")
    label = models.CharField("libellé", max_length=50, default="Maison")
    city = models.CharField("ville", max_length=100, default="Conakry")
    commune = models.CharField(max_length=100, blank=True)
    quartier = models.CharField(max_length=100, blank=True)
    landmark = models.TextField("repère", help_text="Ex. : derrière la mosquée de Kipé, portail bleu")
    latitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    longitude = models.DecimalField(max_digits=9, decimal_places=6, null=True, blank=True)
    contact_phone = models.CharField("téléphone à appeler", max_length=20)
    is_default = models.BooleanField("par défaut", default=False)

    class Meta:
        verbose_name = "adresse"
        ordering = ["-is_default", "-created_at"]

    def __str__(self):
        return f"{self.label} – {self.quartier or self.commune}, {self.city}"


class AdminLog(models.Model):
    admin = models.ForeignKey(User, on_delete=models.CASCADE, related_name="admin_logs")
    action = models.TextField()
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name = "journal admin"
        ordering = ["-created_at"]

    def __str__(self):
        return f"{self.admin} : {self.action[:50]}"
