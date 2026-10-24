"""
Numéros de téléphone guinéens : toujours 9 chiffres (ex. 620112233).

La saisie est nettoyée avant validation : espaces, points, tirets et indicatif
+224 / 00224 sont retirés. « +224 620 11 22 33 » devient « 620112233 ».
"""

import re

from django.core.exceptions import ValidationError
from django.db import models
from rest_framework import serializers

PHONE_LENGTH = 9
PHONE_ERROR = "Le numéro doit contenir exactement 9 chiffres (ex. 620 11 22 33)."


def clean_phone(value):
    digits = re.sub(r"\D", "", str(value or ""))
    if digits.startswith("00224"):
        digits = digits[5:]
    elif digits.startswith("224") and len(digits) == PHONE_LENGTH + 3:
        digits = digits[3:]
    return digits


def validate_phone(value):
    if value and not re.fullmatch(rf"\d{{{PHONE_LENGTH}}}", value):
        raise ValidationError(PHONE_ERROR)


class PhoneField(models.CharField):
    """Champ téléphone des modèles (admin Django compris)."""

    default_validators = [validate_phone]

    def __init__(self, *args, **kwargs):
        kwargs["max_length"] = PHONE_LENGTH
        super().__init__(*args, **kwargs)

    def to_python(self, value):
        value = super().to_python(value)
        return clean_phone(value) if value else value

    def pre_save(self, instance, add):
        value = clean_phone(getattr(instance, self.attname)) if getattr(instance, self.attname) else ""
        setattr(instance, self.attname, value)
        return value


class PhoneSerializerField(serializers.CharField):
    """Champ téléphone de l'API : nettoie la saisie puis exige 9 chiffres."""

    default_error_messages = {"invalid_phone": PHONE_ERROR}

    def __init__(self, **kwargs):
        kwargs.pop("max_length", None)
        super().__init__(**kwargs)

    def to_internal_value(self, data):
        raw = super().to_internal_value(data)
        value = clean_phone(raw)
        # Champ vidé volontairement : accepté s'il est facultatif. Toute autre saisie doit donner 9 chiffres.
        if raw.strip() and len(value) != PHONE_LENGTH:
            self.fail("invalid_phone")
        return value
