from django.db.models import Count, Q
from rest_framework import generics, mixins, parsers, permissions, status, viewsets
from rest_framework.decorators import action
from rest_framework.exceptions import ValidationError
from rest_framework.pagination import CursorPagination
from rest_framework.response import Response

from apps.catalog.models import Product
from apps.notifications.services import push_to_user
from apps.vendors.views import public_vendors

from . import services
from .models import Conversation
from .serializers import ConversationSerializer, MessageSerializer


class MessagePagination(CursorPagination):
    page_size = 30
    ordering = "-created_at"


class ConversationViewSet(mixins.ListModelMixin, mixins.RetrieveModelMixin, viewsets.GenericViewSet):
    """
    GET /conversations/ · POST /conversations/start/ {shop, product?}
    GET|POST /conversations/<id>/messages/ · POST /conversations/<id>/read/ · POST /conversations/<id>/typing/
    """

    serializer_class = ConversationSerializer
    permission_classes = [permissions.IsAuthenticated]
    pagination_class = None

    def get_queryset(self):
        user = self.request.user
        return (
            Conversation.objects.filter(Q(client=user) | Q(vendor__user=user))
            .select_related("client", "vendor")
            .annotate(unread=Count("messages", filter=Q(messages__read_at__isnull=True) & ~Q(messages__sender=user)))
        )

    @action(detail=False, methods=["post"])
    def start(self, request):
        """Ouvre (ou retrouve) la conversation avec une boutique, éventuellement à propos d'un produit."""
        vendor = generics.get_object_or_404(public_vendors(), slug=request.data.get("shop"))
        if vendor.user_id == request.user.id:
            raise ValidationError({"detail": "Vous ne pouvez pas vous écrire à vous-même."})
        conversation, _ = Conversation.objects.get_or_create(client=request.user, vendor=vendor)
        product_slug = request.data.get("product")
        if product_slug:
            product = Product.objects.alive().filter(slug=product_slug, vendor=vendor).first()
            if product and not conversation.messages.filter(product=product).exists():
                serializer = MessageSerializer(
                    data={"content": f"Bonjour, je suis intéressé(e) par « {product.name} »."},
                    context={"request": request},
                )
                serializer.is_valid(raise_exception=True)
                serializer.validated_data["product"] = product
                services.send_message(conversation, request.user, serializer)
        return Response(self.get_serializer(self.get_queryset().get(pk=conversation.pk)).data)

    @action(
        detail=True, methods=["get", "post"],
        parser_classes=[parsers.JSONParser, parsers.MultiPartParser, parsers.FormParser],
    )
    def messages(self, request, pk=None):
        conversation = self.get_object()
        if request.method == "GET":
            paginator = MessagePagination()
            messages = conversation.messages.select_related("product").prefetch_related("product__images")
            page = paginator.paginate_queryset(messages, request, view=self)
            return paginator.get_paginated_response(MessageSerializer(page, many=True, context={"request": request}).data)

        serializer = MessageSerializer(data=request.data, context={"request": request})
        serializer.is_valid(raise_exception=True)
        message = services.send_message(conversation, request.user, serializer)
        return Response(MessageSerializer(message, context={"request": request}).data, status=status.HTTP_201_CREATED)

    @action(detail=True, methods=["post"])
    def read(self, request, pk=None):
        services.mark_read(self.get_object(), request.user)
        return Response(status=status.HTTP_204_NO_CONTENT)

    @action(detail=True, methods=["post"])
    def typing(self, request, pk=None):
        conversation = self.get_object()
        other = services.recipient_of(conversation, request.user)
        push_to_user(other.id, {"type": "chat.typing", "conversation": conversation.id})
        return Response(status=status.HTTP_204_NO_CONTENT)
