"""
Configuration Django de GuinéeMall v2.

Toutes les valeurs sensibles ou dépendantes de l'environnement sont lues
depuis le fichier .env (voir .env.example).
"""

import sys
from datetime import timedelta
from pathlib import Path

import environ

BASE_DIR = Path(__file__).resolve().parent.parent

env = environ.Env(
    DEBUG=(bool, False),
    ALLOWED_HOSTS=(list, ["localhost", "127.0.0.1"]),
    CORS_ALLOWED_ORIGINS=(list, ["http://localhost:5173"]),
)
environ.Env.read_env(BASE_DIR / ".env")

SECRET_KEY = env("SECRET_KEY")
DEBUG = env("DEBUG")
ALLOWED_HOSTS = env("ALLOWED_HOSTS")

INSTALLED_APPS = [
    "daphne",
    "django.contrib.admin",
    "django.contrib.auth",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
    "django.contrib.postgres",
    # Tiers
    "rest_framework",
    "rest_framework_simplejwt",
    "corsheaders",
    "django_filters",
    "channels",
    # GuinéeMall
    "apps.core",
    "apps.accounts",
    "apps.vendors",
    "apps.catalog",
    "apps.orders",
    "apps.chat",
    "apps.statuts",
    "apps.notifications",
    "apps.wallet",
    "apps.payments",
    "apps.promotions",
    "apps.delivery",
    "apps.lives",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "corsheaders.middleware.CorsMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
]

ROOT_URLCONF = "config.urls"

TEMPLATES = [
    {
        "BACKEND": "django.template.backends.django.DjangoTemplates",
        "DIRS": [],
        "APP_DIRS": True,
        "OPTIONS": {
            "context_processors": [
                "django.template.context_processors.request",
                "django.contrib.auth.context_processors.auth",
                "django.contrib.messages.context_processors.messages",
            ],
        },
    },
]

WSGI_APPLICATION = "config.wsgi.application"
ASGI_APPLICATION = "config.asgi.application"

# Base de données : PostgreSQL
# Exemple : DATABASE_URL=postgres://guineemall:motdepasse@localhost:5432/guineemall
DATABASES = {"default": env.db("DATABASE_URL")}

# Redis : cache + couche temps réel (chat, notifications)
REDIS_URL = env("REDIS_URL", default="redis://localhost:6379/0")

CACHES = {
    "default": {
        "BACKEND": "django.core.cache.backends.redis.RedisCache",
        "LOCATION": REDIS_URL,
    }
}

TESTING = "test" in sys.argv
if TESTING:
    # Les tests ne doivent jamais lire ni écrire le cache du serveur de développement
    CACHES = {"default": {"BACKEND": "django.core.cache.backends.locmem.LocMemCache"}}

CHANNEL_LAYERS = {
    "default": {
        "BACKEND": "channels_redis.core.RedisChannelLayer",
        "CONFIG": {"hosts": [REDIS_URL]},
    }
}

AUTH_USER_MODEL = "accounts.User"

AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator"},
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator"},
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},
    {"NAME": "django.contrib.auth.password_validation.NumericPasswordValidator"},
]

LANGUAGE_CODE = "fr"
TIME_ZONE = "Africa/Conakry"
USE_I18N = True
USE_TZ = True
# Nombres affichés « 1 000 000 » dans l'admin
USE_THOUSAND_SEPARATOR = True

STATIC_URL = "static/"
STATIC_ROOT = BASE_DIR / "staticfiles"
MEDIA_URL = "media/"
MEDIA_ROOT = BASE_DIR / "media"

DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

# API REST
REST_FRAMEWORK = {
    "DEFAULT_AUTHENTICATION_CLASSES": [
        "rest_framework_simplejwt.authentication.JWTAuthentication",
    ],
    "DEFAULT_PERMISSION_CLASSES": [
        "rest_framework.permissions.IsAuthenticatedOrReadOnly",
    ],
    "DEFAULT_FILTER_BACKENDS": [
        "django_filters.rest_framework.DjangoFilterBackend",
        "rest_framework.filters.SearchFilter",
        "rest_framework.filters.OrderingFilter",
    ],
    "DEFAULT_PAGINATION_CLASS": "apps.core.pagination.Pagination",
}

SIMPLE_JWT = {
    "ACCESS_TOKEN_LIFETIME": timedelta(minutes=30),
    "REFRESH_TOKEN_LIFETIME": timedelta(days=30),
    "ROTATE_REFRESH_TOKENS": True,
    "UPDATE_LAST_LOGIN": True,
}

CORS_ALLOWED_ORIGINS = env("CORS_ALLOWED_ORIGINS")

# Statuts : durée de vie en heures
STATUT_DURATION_HOURS = 24

# Tâches en arrière-plan (Celery + Redis)
CELERY_BROKER_URL = REDIS_URL
CELERY_RESULT_BACKEND = None
# Pendant les tests, les tâches s'exécutent immédiatement (pas besoin de worker)
CELERY_TASK_ALWAYS_EAGER = env.bool("CELERY_TASK_ALWAYS_EAGER", default=TESTING)
CELERY_TIMEZONE = TIME_ZONE
CELERY_BEAT_SCHEDULE = {
    "annuler-commandes-non-payees": {"task": "apps.payments.tasks.expire_unpaid_orders", "schedule": 300},
    "liberer-paiements-sequestre": {"task": "apps.wallet.tasks.release_escrow", "schedule": 3600},
    "supprimer-medias-statuts-expires": {"task": "apps.statuts.tasks.purge_expired_statuts", "schedule": 86400},
}

# URL publique du site (liens dans les notifications, retours de paiement)
FRONTEND_URL = env("FRONTEND_URL", default="http://localhost:5173")
BACKEND_URL = env("BACKEND_URL", default="http://localhost:8000")

# Place de marché
PLATFORM_COMMISSION_RATE = env.float("PLATFORM_COMMISSION_RATE", default=0.08)  # 8 % par vente
AFFILIATE_COMMISSION_RATE = env.float("AFFILIATE_COMMISSION_RATE", default=0.02)  # 2 % pour l'ambassadeur
ESCROW_AUTO_RELEASE_DAYS = 3  # paiement versé au vendeur si le client ne confirme pas la réception
UNPAID_ORDER_TIMEOUT_MINUTES = 30
REFERRAL_REWARD_GNF = 10_000

# Paiements : "simulation" en développement, "live" avec les vraies clés
PAYMENT_MODE = env("PAYMENT_MODE", default="simulation")
ORANGE_MONEY = {
    "CLIENT_ID": env("ORANGE_MONEY_CLIENT_ID", default=""),
    "CLIENT_SECRET": env("ORANGE_MONEY_CLIENT_SECRET", default=""),
    "MERCHANT_KEY": env("ORANGE_MONEY_MERCHANT_KEY", default=""),
    "API_URL": env("ORANGE_MONEY_API_URL", default="https://api.orange.com"),
    "COUNTRY": env("ORANGE_MONEY_COUNTRY", default="gn"),
    "CURRENCY": env("ORANGE_MONEY_CURRENCY", default="GNF"),
}
MTN_MOMO = {
    "API_URL": env("MTN_MOMO_API_URL", default="https://sandbox.momodeveloper.mtn.com"),
    "SUBSCRIPTION_KEY": env("MTN_MOMO_SUBSCRIPTION_KEY", default=""),
    "API_USER": env("MTN_MOMO_API_USER", default=""),
    "API_KEY": env("MTN_MOMO_API_KEY", default=""),
    "TARGET_ENVIRONMENT": env("MTN_MOMO_TARGET_ENVIRONMENT", default="sandbox"),
    "CURRENCY": env("MTN_MOMO_CURRENCY", default="GNF"),
}
STRIPE = {
    "SECRET_KEY": env("STRIPE_SECRET_KEY", default=""),
    "WEBHOOK_SECRET": env("STRIPE_WEBHOOK_SECRET", default=""),
}
# Taux indicatifs pour la diaspora (1 EUR / 1 USD en GNF), à mettre à jour par l'admin
EXCHANGE_RATES = {"EUR": env.int("RATE_EUR_GNF", default=9400), "USD": env.int("RATE_USD_GNF", default=8600)}

# Notifications WhatsApp (API WhatsApp Cloud de Meta). Vide = messages affichés dans la console.
WHATSAPP = {
    "TOKEN": env("WHATSAPP_TOKEN", default=""),
    "PHONE_NUMBER_ID": env("WHATSAPP_PHONE_NUMBER_ID", default=""),
    "TEMPLATE": env("WHATSAPP_TEMPLATE", default="guineemall_notification"),
    "LANGUAGE": "fr",
}

# Emails (mot de passe oublié). Sans SMTP configuré, les emails s'affichent dans la console.
EMAIL_BACKEND = env("EMAIL_BACKEND", default="django.core.mail.backends.console.EmailBackend")
EMAIL_HOST = env("EMAIL_HOST", default="")
EMAIL_PORT = env.int("EMAIL_PORT", default=587)
EMAIL_HOST_USER = env("EMAIL_HOST_USER", default="")
EMAIL_HOST_PASSWORD = env("EMAIL_HOST_PASSWORD", default="")
EMAIL_USE_TLS = env.bool("EMAIL_USE_TLS", default=True)
DEFAULT_FROM_EMAIL = env("DEFAULT_FROM_EMAIL", default="GuinéeMall <no-reply@guineemall.gn>")
