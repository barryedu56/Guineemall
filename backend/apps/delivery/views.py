from django.db import transaction
from rest_framework import generics, mixins, permissions, serializers, status, viewsets
from rest_framework.decorators import action
from rest_framework.exceptions import ValidationError
from rest_framework.permissions import BasePermission
from rest_framework.response import Response

from apps.accounts.models import User
from apps.accounts.serializers import RegisterSerializer
from apps.orders.services import OrderError

from . import services
from .models import Delivery, DeliveryZone, PickupPoint, Rider
from .serializers import DeliveryZoneSerializer, PickupPointSerializer, RiderDeliverySerializer, RiderSerializer


class DeliveryZoneViewSet(viewsets.ReadOnlyModelViewSet):
    queryset = DeliveryZone.objects.filter(is_active=True)
    serializer_class = DeliveryZoneSerializer
    pagination_class = None
    permission_classes = [permissions.AllowAny]


class PickupPointViewSet(viewsets.ReadOnlyModelViewSet):
    queryset = PickupPoint.objects.filter(is_active=True)
    serializer_class = PickupPointSerializer
    pagination_class = None
    permission_classes = [permissions.AllowAny]
    filterset_fields = ["city"]


class IsApprovedRider(BasePermission):
    message = "Votre compte livreur doit être validé par GuinéeMall."

    def has_permission(self, request, view):
        rider = getattr(request.user, "rider", None) if request.user.is_authenticated else None
        return rider is not None and rider.is_approved


class RiderRegisterSerializer(RegisterSerializer):
    city = serializers.CharField(max_length=100, default="Conakry", write_only=True)
    communes = serializers.CharField(max_length=255, required=False, allow_blank=True, write_only=True)
    vehicle_plate = serializers.CharField(max_length=20, required=False, allow_blank=True, write_only=True)

    class Meta(RegisterSerializer.Meta):
        fields = RegisterSerializer.Meta.fields + ["city", "communes", "vehicle_plate"]

    @transaction.atomic
    def create(self, validated_data):
        rider = {k: validated_data.pop(k, "") for k in ("city", "communes", "vehicle_plate")}
        user = User.objects.create_user(role=User.Role.LIVREUR, **self._with_referral(validated_data))
        Rider.objects.create(user=user, **rider)
        return user


class RiderRegisterView(generics.CreateAPIView):
    serializer_class = RiderRegisterSerializer
    permission_classes = [permissions.AllowAny]


class RiderProfileView(generics.RetrieveUpdateAPIView):
    serializer_class = RiderSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_object(self):
        return generics.get_object_or_404(Rider, user=self.request.user)


class LocationSerializer(serializers.Serializer):
    latitude = serializers.DecimalField(max_digits=9, decimal_places=6)
    longitude = serializers.DecimalField(max_digits=9, decimal_places=6)


class RiderDeliveryViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, viewsets.GenericViewSet):
    """
    GET /rider/deliveries/ : mes courses · GET /rider/deliveries/available/ : courses libres dans ma ville
    POST /rider/deliveries/<id>/accept|pickup|deliver/ · POST /rider/deliveries/location/
    """

    serializer_class = RiderDeliverySerializer
    permission_classes = [IsApprovedRider]

    def base_queryset(self):
        return Delivery.objects.select_related(
            "vendor_order__order__user", "vendor_order__vendor__user"
        ).prefetch_related("vendor_order__items")

    def get_queryset(self):
        if self.action in ("available", "accept"):
            return self.base_queryset().filter(
                status=Delivery.Status.OPEN, vendor_order__order__delivery_city__iexact=self.request.user.rider.city
            )
        return self.base_queryset().filter(rider=self.request.user.rider)

    @action(detail=False)
    def available(self, request):
        return Response(self.get_serializer(self.get_queryset(), many=True).data)

    def _run(self, fn):
        try:
            delivery = fn(self.get_object(), self.request.user.rider)
        except OrderError as e:
            raise ValidationError({"detail": str(e)})
        return Response(self.get_serializer(self.base_queryset().get(pk=delivery.pk)).data)

    @action(detail=True, methods=["post"])
    def accept(self, request, pk=None):
        return self._run(services.accept)

    @action(detail=True, methods=["post"])
    def pickup(self, request, pk=None):
        return self._run(services.picked_up)

    @action(detail=True, methods=["post"])
    def deliver(self, request, pk=None):
        return self._run(services.delivered)

    @action(detail=False, methods=["post"])
    def location(self, request):
        payload = LocationSerializer(data=request.data)
        payload.is_valid(raise_exception=True)
        services.update_location(request.user.rider, **payload.validated_data)
        return Response(status=status.HTTP_204_NO_CONTENT)
