from django.urls import path

from . import views

urlpatterns = [
    path("payments/<uuid:reference>/", views.payment_status, name="payment-status"),
    path("payments/callback/<uuid:reference>/", views.provider_callback, name="payment-callback"),
    path("payments/simulation/<uuid:reference>/", views.simulate, name="payment-simulate"),
]
