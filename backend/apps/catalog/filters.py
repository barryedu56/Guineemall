import django_filters
from django.db import models

from .models import Product


class ProductFilter(django_filters.FilterSet):
    category = django_filters.CharFilter(field_name="category__slug")
    vendor = django_filters.CharFilter(field_name="vendor__slug")
    min_price = django_filters.NumberFilter(field_name="price", lookup_expr="gte")
    max_price = django_filters.NumberFilter(field_name="price", lookup_expr="lte")
    in_stock = django_filters.BooleanFilter(method="filter_in_stock")
    on_sale = django_filters.BooleanFilter(method="filter_on_sale")

    class Meta:
        model = Product
        fields = ["category", "vendor", "min_price", "max_price", "in_stock", "on_sale"]

    def filter_in_stock(self, queryset, name, value):
        return queryset.filter(stock__gt=0) if value else queryset

    def filter_on_sale(self, queryset, name, value):
        return queryset.filter(compare_at_price__gt=models.F("price")) if value else queryset
