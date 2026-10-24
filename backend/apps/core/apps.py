from django.apps import AppConfig


class CoreConfig(AppConfig):
    default_auto_field = "django.db.models.BigAutoField"
    name = "apps.core"

    def ready(self):
        # Tous les champs téléphone des modèles deviennent automatiquement des champs
        # téléphone dans l'API (nettoyage + 9 chiffres exactement)
        from rest_framework import serializers

        from .phone import PhoneField, PhoneSerializerField

        serializers.ModelSerializer.serializer_field_mapping[PhoneField] = PhoneSerializerField
