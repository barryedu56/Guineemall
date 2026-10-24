from django.db import models
from django.utils import timezone
from django.utils.text import slugify


class TimeStampedModel(models.Model):
    """Ajoute created_at / updated_at (équivalent de $table->timestamps())."""

    created_at = models.DateTimeField("créé le", auto_now_add=True)
    updated_at = models.DateTimeField("modifié le", auto_now=True)

    class Meta:
        abstract = True


class SoftDeleteQuerySet(models.QuerySet):
    def delete(self):
        return self.update(deleted_at=timezone.now())

    def alive(self):
        return self.filter(deleted_at__isnull=True)


class SoftDeleteModel(models.Model):
    """Suppression douce (équivalent de $table->softDeletes())."""

    deleted_at = models.DateTimeField("supprimé le", null=True, blank=True, editable=False)

    objects = SoftDeleteQuerySet.as_manager()

    class Meta:
        abstract = True

    def delete(self, using=None, keep_parents=False):
        self.deleted_at = timezone.now()
        self.save(update_fields=["deleted_at"])

    def hard_delete(self):
        return super().delete()

    @property
    def is_deleted(self):
        return self.deleted_at is not None


def unique_slug(instance, value, field="slug", fallback="item"):
    """Slug unique pour le modèle de l'instance : « sac-a-main », « sac-a-main-2 »…"""
    base = slugify(value) or fallback
    slug, n = base, 1
    model = type(instance)
    while model._default_manager.filter(**{field: slug}).exclude(pk=instance.pk).exists():
        n += 1
        slug = f"{base}-{n}"
    return slug
