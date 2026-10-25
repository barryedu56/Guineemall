from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as BaseUserAdmin

from .models import Address, User


@admin.register(User)
class UserAdmin(BaseUserAdmin):
    ordering = ["-date_joined"]
    list_display = ["email", "name", "role", "phone", "is_active"]
    list_filter = ["role", "is_active", "is_staff"]
    search_fields = ["email", "name", "phone"]
    fieldsets = [
        (None, {"fields": ["email", "password"]}),
        ("Profil", {"fields": ["name", "phone", "role", "avatar"]}),
        ("Permissions", {"fields": ["is_active", "is_staff", "is_superuser", "groups", "user_permissions"]}),
        ("Dates", {"fields": ["last_login", "date_joined"]}),
    ]
    add_fieldsets = [
        (None, {"classes": ["wide"], "fields": ["email", "name", "role", "password1", "password2"]}),
    ]


@admin.register(Address)
class AddressAdmin(admin.ModelAdmin):
    list_display = ["user", "label", "city", "commune", "quartier", "is_default"]
    list_filter = ["city", "commune"]
