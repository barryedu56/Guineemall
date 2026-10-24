from rest_framework import generics, permissions, viewsets
from rest_framework.response import Response

from .models import Address
from .serializers import AddressSerializer, RegisterSerializer, UserSerializer, VendorRegisterSerializer


class RegisterView(generics.CreateAPIView):
    serializer_class = RegisterSerializer
    permission_classes = [permissions.AllowAny]


class VendorRegisterView(generics.CreateAPIView):
    serializer_class = VendorRegisterSerializer
    permission_classes = [permissions.AllowAny]


class MeView(generics.RetrieveUpdateAPIView):
    serializer_class = UserSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_object(self):
        return self.request.user


class ReferralView(generics.GenericAPIView):
    """Parrainage / ambassadeur : code, lien à partager, filleuls, gains et bons disponibles."""

    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        from django.conf import settings
        from django.db.models import Sum
        from django.utils import timezone

        from apps.wallet.models import LedgerEntry

        user = request.user
        coupons = user.personal_coupons.filter(is_active=True, used_count=0).exclude(ends_at__lt=timezone.now())
        return Response({
            "code": user.referral_code,
            "link": f"{settings.FRONTEND_URL}/?ref={user.referral_code}",
            "referrals": user.referrals.count(),
            "affiliate_orders": user.affiliated_orders.exclude(status="cancelled").count(),
            "affiliate_earnings": LedgerEntry.objects.filter(user=user, kind=LedgerEntry.Kind.AFFILIATE)
            .aggregate(t=Sum("amount"))["t"] or 0,
            "reward": settings.REFERRAL_REWARD_GNF,
            "affiliate_rate": settings.AFFILIATE_COMMISSION_RATE,
            "coupons": [{"code": c.code, "value": c.value, "min_amount": c.min_amount, "description": c.description} for c in coupons],
        })


class AddressViewSet(viewsets.ModelViewSet):
    serializer_class = AddressSerializer
    permission_classes = [permissions.IsAuthenticated]
    pagination_class = None

    def get_queryset(self):
        return self.request.user.addresses.all()

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)
