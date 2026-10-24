from rest_framework import serializers

from .models import Conversation, Message


class MessageSerializer(serializers.ModelSerializer):
    product = serializers.SerializerMethodField()

    class Meta:
        model = Message
        fields = ["id", "conversation", "sender", "type", "content", "attachment", "product", "order", "read_at", "created_at"]
        read_only_fields = ["conversation", "sender", "read_at"]

    def get_product(self, message):
        p = message.product
        if p is None:
            return None
        image = p.images.first()
        return {"slug": p.slug, "name": p.name, "price": p.price, "image": image.image.url if image else None}

    def validate(self, attrs):
        if not attrs.get("content", "").strip() and not attrs.get("attachment"):
            raise serializers.ValidationError("Message vide.")
        attachment = attrs.get("attachment")
        if attachment:
            content_type = getattr(attachment, "content_type", "")
            if attachment.size > 15 * 1024 * 1024:
                raise serializers.ValidationError({"attachment": "Fichier trop lourd (15 Mo maximum)."})
            attrs["type"] = (
                Message.Type.IMAGE if content_type.startswith("image/")
                else Message.Type.VOICE if content_type.startswith("audio/")
                else Message.Type.FILE
            )
        else:
            attrs["type"] = Message.Type.TEXT
        return attrs


class ConversationSerializer(serializers.ModelSerializer):
    """Conversation vue par l'un des deux participants."""

    other = serializers.SerializerMethodField()
    last_message = serializers.SerializerMethodField()
    unread = serializers.IntegerField(read_only=True, default=0)

    class Meta:
        model = Conversation
        fields = ["id", "other", "last_message", "unread", "last_message_at"]

    def get_other(self, conversation):
        user = self.context["request"].user
        if user.id == conversation.client_id:
            v = conversation.vendor
            return {"kind": "shop", "name": v.shop_name, "slug": v.slug, "logo": v.logo.url if v.logo else None,
                    "is_verified": v.is_verified}
        c = conversation.client
        return {"kind": "client", "name": c.name, "slug": None, "logo": c.avatar.url if c.avatar else None,
                "is_verified": False}

    def get_last_message(self, conversation):
        m = conversation.messages.order_by("-created_at").first()
        if m is None:
            return None
        preview = {"image": "📷 Photo", "voice": "🎤 Message vocal", "file": "📎 Fichier"}.get(m.type, m.content)
        return {"preview": preview[:80], "sender": m.sender_id, "created_at": m.created_at}
