from django.contrib import admin

from .models import Category, Favorite, Product, ProductImage


class ProductImageInline(admin.TabularInline):
    model = ProductImage
    extra = 1


@admin.register(Category)
class CategoryAdmin(admin.ModelAdmin):
    list_display = ["name", "parent", "status"]
    prepopulated_fields = {"slug": ["name"]}


@admin.register(Product)
class ProductAdmin(admin.ModelAdmin):
    list_display = ["name", "vendor", "category", "price", "stock", "status"]
    list_filter = ["status", "category"]
    search_fields = ["name", "vendor__shop_name"]
    inlines = [ProductImageInline]


admin.site.register(Favorite)
