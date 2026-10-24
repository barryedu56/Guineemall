from django.contrib import admin

from .models import LiveComment, LiveSession


@admin.register(LiveSession)
class LiveSessionAdmin(admin.ModelAdmin):
    list_display = ["title", "vendor", "starts_at", "status"]
    list_filter = ["status"]


admin.site.register(LiveComment)
