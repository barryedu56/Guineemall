"""
Logique métier des commandes (reprise de App\\Services\\Client\\OrderService en Laravel).

Différences avec l'ancienne version :
- le stock est remis quand un vendeur annule sa partie de commande ;
- quand le client annule, seules les parties encore annulables le sont,
  et le stock n'est remis que pour celles-ci ;
- une commande dont toutes les parties sont terminées (livrées ou annulées,
  avec au moins une livrée) passe en « livrée » au lieu de rester bloquée.

Ajouts v2 : frais de livraison par commune ou point relais, codes promo,
ambassadeurs, paiement en ligne avec séquestre, livreurs, notifications.
"""

import hmac
import secrets
from collections import defaultdict
from dataclasses import dataclass, field

from django.conf import settings
from django.db import transaction
from django.db.models import F
from django.utils import timezone

from apps.catalog.models import Product
from apps.core.formatting import gnf
from apps.delivery.models import Delivery, DeliveryZone
from apps.notifications.services import notify
from apps.promotions.models import CouponRedemption
from apps.promotions.services import CouponError, create_referral_coupon, find_valid_coupon
from apps.vendors.models import Vendor

from .models import CartItem, Order, OrderItem, OrderStatusHistory, VendorOrder


class OrderError(Exception):
    """Erreur métier affichable telle quelle au client."""


# Côté boutique, deux gestes seulement : « Accepter » (la préparation commence) puis « Colis prêt ».
# « Confirmée » n'est plus utilisé que par les anciennes commandes.
VENDOR_ORDER_TRANSITIONS = {
    VendorOrder.Status.PENDING: [VendorOrder.Status.PREPARING, VendorOrder.Status.CANCELLED],
    VendorOrder.Status.CONFIRMED: [VendorOrder.Status.PREPARING, VendorOrder.Status.CANCELLED],
    VendorOrder.Status.PREPARING: [VendorOrder.Status.READY, VendorOrder.Status.CANCELLED],
    VendorOrder.Status.READY: [VendorOrder.Status.DELIVERED],
    VendorOrder.Status.DELIVERED: [],
    VendorOrder.Status.CANCELLED: [],
}

CANCELLABLE_VENDOR_STATUSES = [VendorOrder.Status.PENDING, VendorOrder.Status.CONFIRMED]
# Payées avant la livraison : argent en séquestre, versé après confirmation de réception
ONLINE_METHODS = [
    Order.PaymentMethod.ORANGE_MONEY, Order.PaymentMethod.MTN_MOMO, Order.PaymentMethod.CARD, Order.PaymentMethod.STORE_CREDIT,
]


def new_delivery_code():
    return f"{secrets.randbelow(10_000):04d}"


def check_delivery_code(vendor_order, code):
    """
    Vérifie le code de livraison donné par le client. À appeler hors transaction :
    chaque code faux est compté, et après DELIVERY_CODE_MAX_ATTEMPTS la livraison est bloquée
    jusqu'à ce qu'un admin la débloque (protection contre les essais au hasard).
    """
    vendor_order.refresh_from_db(fields=["delivery_code", "delivery_code_attempts"])
    if vendor_order.delivery_code_attempts >= settings.DELIVERY_CODE_MAX_ATTEMPTS:
        raise OrderError("Trop de codes faux : la livraison est bloquée. Contactez le service client.")
    if not vendor_order.delivery_code or not hmac.compare_digest(vendor_order.delivery_code, str(code or "").strip()):
        VendorOrder.objects.filter(pk=vendor_order.pk).update(delivery_code_attempts=F("delivery_code_attempts") + 1)
        left = settings.DELIVERY_CODE_MAX_ATTEMPTS - vendor_order.delivery_code_attempts - 1
        if left <= 0:
            from apps.notifications.services import notify_team

            notify_team(
                f"Code de livraison bloqué – commande #{vendor_order.order_id}",
                f"{settings.DELIVERY_CODE_MAX_ATTEMPTS} codes faux saisis ({vendor_order.vendor}). Vérifiez avant de débloquer.",
            )
        raise OrderError(
            "Code de livraison incorrect. Demandez au client le code reçu par notification"
            + (f" ({left} essai(s) restant(s))." if left > 0 else ". La livraison est maintenant bloquée.")
        )
    return True


def allowed_vendor_transitions(vendor_order):
    return VENDOR_ORDER_TRANSITIONS[vendor_order.status]


# ---------------------------------------------------------------- Devis

@dataclass
class VendorQuote:
    vendor: Vendor
    lines: list  # [(product, quantity)]
    subtotal: int
    delivery_fee: int
    vendor_discount: int = 0
    platform_discount: int = 0

    @property
    def total(self):
        return self.subtotal + self.delivery_fee - self.vendor_discount - self.platform_discount


@dataclass
class Quote:
    vendors: list = field(default_factory=list)
    coupon: object = None
    coupon_error: str = ""
    delivery_delay: str = ""

    @property
    def subtotal(self):
        return sum(v.subtotal for v in self.vendors)

    @property
    def delivery_fee(self):
        return sum(v.delivery_fee for v in self.vendors)

    @property
    def discount(self):
        return sum(v.vendor_discount + v.platform_discount for v in self.vendors)

    @property
    def total(self):
        return self.subtotal + self.delivery_fee - self.discount


def _delivery_fee(address, pickup_point):
    if pickup_point is not None:
        return pickup_point.fee, "Dès que la boutique a déposé le colis"
    zone = DeliveryZone.find(address.city, address.commune)
    if zone is None:
        raise OrderError(
            f"La livraison n'est pas encore disponible à {address.commune or address.city}. "
            "Choisissez un point relais ou une autre adresse."
        )
    return zone.fee, zone.delay


def build_quote(user, cart_lines, address=None, pickup_point=None, coupon_code="", strict_coupon=False):
    """
    Calcule le détail d'une commande sans l'enregistrer.
    `cart_lines` : liste de (product, quantity). Chaque boutique livre sa partie,
    donc les frais de livraison s'appliquent une fois par boutique.
    """
    if address is None and pickup_point is None:
        raise OrderError("Choisissez une adresse de livraison ou un point relais.")
    fee, delay = _delivery_fee(address, pickup_point)

    by_vendor = defaultdict(list)
    for product, qty in cart_lines:
        by_vendor[product.vendor_id].append((product, qty))

    quote = Quote(delivery_delay=delay)
    for lines in by_vendor.values():
        quote.vendors.append(
            VendorQuote(vendor=lines[0][0].vendor, lines=lines, subtotal=sum(p.price * q for p, q in lines), delivery_fee=fee)
        )

    if coupon_code:
        try:
            coupon, eligible = find_valid_coupon(coupon_code, user, cart_lines)
        except CouponError as e:
            if strict_coupon:
                raise OrderError(str(e))
            quote.coupon_error = str(e)
        else:
            quote.coupon = coupon
            discount = coupon.compute_discount(eligible)
            if coupon.vendor_id:
                vq = next(v for v in quote.vendors if v.vendor.id == coupon.vendor_id)
                vq.vendor_discount = discount
            else:
                # Réduction offerte par GuinéeMall, répartie au prorata entre les boutiques
                remaining = discount
                for i, vq in enumerate(quote.vendors):
                    share = remaining if i == len(quote.vendors) - 1 else discount * vq.subtotal // quote.subtotal
                    vq.platform_discount = share
                    remaining -= share
    return quote


# ---------------------------------------------------------------- Passage de commande

@transaction.atomic
def checkout(user, payment_method, address=None, pickup_point=None, coupon_code="", affiliate_code="", delivery_name="",
             use_credit=False):
    """Transforme le panier en commande, découpée en une sous-commande par boutique."""
    from apps.accounts.models import User
    from apps.wallet import credit as avoir

    cart = list(CartItem.objects.filter(user=user).select_related("product__vendor"))
    if not cart:
        raise OrderError("Votre panier est vide.")

    # Verrouille les produits pour éviter de vendre deux fois le dernier article
    products = Product.objects.select_for_update().select_related("vendor").in_bulk([i.product_id for i in cart])
    lines = []
    for item in cart:
        product = products[item.product_id]
        if product.is_owned_by(user):
            raise OrderError(f"« {product.name} » est un produit de votre boutique : retirez-le du panier.")
        if (
            product.is_deleted
            or product.status != Product.Status.ACTIVE
            or product.vendor.status != Vendor.Status.APPROVED
        ):
            raise OrderError(f"Le produit « {product.name} » n'est plus disponible.")
        if product.stock < item.quantity:
            raise OrderError(
                f"Stock insuffisant pour « {product.name} » : "
                f"demandé {item.quantity}, disponible {product.stock}."
            )
        lines.append((product, item.quantity))

    quote = build_quote(user, lines, address, pickup_point, coupon_code, strict_coupon=True)

    credit = 0
    if use_credit:
        User.objects.select_for_update().get(pk=user.pk)  # l'avoir ne peut pas être dépensé deux fois
        credit = min(max(0, avoir.balance(user)), quote.total)
    paid_by_credit = credit > 0 and credit == quote.total
    if paid_by_credit:
        payment_method = Order.PaymentMethod.STORE_CREDIT
    elif credit and payment_method == Order.PaymentMethod.CASH_ON_DELIVERY:
        raise OrderError("Votre avoir ne couvre pas toute la commande : payez le reste en ligne (Orange Money, MTN ou carte).")

    affiliate = None
    if affiliate_code:
        affiliate = User.objects.filter(referral_code=affiliate_code.upper()).exclude(pk=user.pk).first()

    place = pickup_point if pickup_point else address
    order = Order.objects.create(
        user=user,
        subtotal=quote.subtotal,
        delivery_fee=quote.delivery_fee,
        discount_amount=quote.discount,
        total_amount=quote.total,
        credit_used=credit,
        payment_status=Order.PaymentStatus.PAID if paid_by_credit else Order.PaymentStatus.PENDING,
        coupon=quote.coupon,
        affiliate=affiliate,
        payment_method=payment_method,
        delivery_method=Order.DeliveryMethod.PICKUP if pickup_point else Order.DeliveryMethod.HOME,
        pickup_point=pickup_point,
        delivery_name=delivery_name,
        delivery_city=place.city,
        delivery_commune=place.commune,
        delivery_quartier=getattr(address, "quartier", "") if address else pickup_point.name,
        delivery_landmark=address.landmark if address else pickup_point.address,
        delivery_latitude=place.latitude,
        delivery_longitude=place.longitude,
        delivery_phone=address.contact_phone if address else user.phone,
    )

    for vq in quote.vendors:
        vendor_order = VendorOrder.objects.create(
            order=order,
            vendor=vq.vendor,
            subtotal=vq.subtotal,
            delivery_fee=vq.delivery_fee,
            vendor_discount=vq.vendor_discount,
            platform_discount=vq.platform_discount,
            total_amount=vq.total,
        )
        OrderItem.objects.bulk_create(
            OrderItem(
                order=order, vendor_order=vendor_order, product=p,
                product_name=p.name, unit_price=p.price, quantity=q,
            )
            for p, q in vq.lines
        )
        for p, q in vq.lines:
            Product.objects.filter(pk=p.pk).update(stock=F("stock") - q)

    if quote.coupon:
        CouponRedemption.objects.create(coupon=quote.coupon, user=user, order=order, amount=quote.discount)
        type(quote.coupon).objects.filter(pk=quote.coupon.pk).update(used_count=F("used_count") + 1)

    OrderStatusHistory.objects.create(order=order, status=order.status, changed_by=user, note="Commande passée")
    if credit:
        avoir.add_credit(user, -credit, avoir.CreditEntry.Kind.USED, order, f"Commande #{order.id}")
        if paid_by_credit:
            OrderStatusHistory.objects.create(order=order, status=order.status, note="Payée avec l'avoir")
    CartItem.objects.filter(user=user).delete()

    # Paiement à la livraison ou avec l'avoir : les boutiques sont prévenues tout de suite.
    # Paiement en ligne : seulement une fois le paiement reçu (voir mark_order_paid).
    if payment_method in (Order.PaymentMethod.CASH_ON_DELIVERY, Order.PaymentMethod.STORE_CREDIT):
        announce_order(order)
    return order


def announce_order(order):
    notify(order.user, "order", f"Commande #{order.id} enregistrée",
           "Les boutiques vont la confirmer et vous appeler pour la livraison.", f"/commandes/{order.id}")
    for vo in order.vendor_orders.select_related("vendor__user"):
        notify(vo.vendor.user, "order", f"Nouvelle commande #{order.id} 🛍️",
               f"{vo.items.count()} article(s) – {gnf(vo.total_amount)}. Acceptez-la rapidement.", f"/vendeur/commandes/{vo.id}")


@transaction.atomic
def mark_order_paid(order):
    order = Order.objects.select_for_update().get(pk=order.pk)
    if order.payment_status == Order.PaymentStatus.PAID:
        return order
    if order.status == Order.Status.CANCELLED:
        # Paiement arrivé après l'expiration de la commande : remboursé en avoir
        from apps.wallet.credit import refund_to_credit

        refund_to_credit(order, order.amount_due, "paiement reçu après l'annulation")
        order.refunded_amount += order.amount_due
        order.payment_status = Order.PaymentStatus.REFUNDED
        order.save(update_fields=["payment_status", "refunded_amount", "updated_at"])
        return order
    order.payment_status = Order.PaymentStatus.PAID
    order.save(update_fields=["payment_status", "updated_at"])
    OrderStatusHistory.objects.create(order=order, status=order.status, note="Paiement reçu")
    announce_order(order)
    return order


def is_visible_to_vendor(order):
    return order.payment_method == Order.PaymentMethod.CASH_ON_DELIVERY or order.payment_status != Order.PaymentStatus.PENDING


# ---------------------------------------------------------------- Statuts

def _restore_stock(vendor_order):
    for item in vendor_order.items.filter(product__isnull=False):
        Product.objects.filter(pk=item.product_id).update(stock=F("stock") + item.quantity)


def _refund(order, vendor_orders, reason):
    """
    Parties annulées d'une commande déjà payée (en ligne ou avec l'avoir) : remboursées tout de suite en avoir.
    Commande annulée avant paiement : l'avoir engagé est rendu.
    """
    from apps.wallet.credit import CreditEntry, add_credit, refund_to_credit

    all_cancelled = not order.vendor_orders.exclude(status=VendorOrder.Status.CANCELLED).exists()
    if order.payment_status == Order.PaymentStatus.PAID and order.payment_method != Order.PaymentMethod.CASH_ON_DELIVERY:
        amount = sum(vo.total_amount for vo in vendor_orders)
        refund_to_credit(order, amount, reason)
        order.refunded_amount += amount
        if all_cancelled:
            order.payment_status = Order.PaymentStatus.REFUNDED
        order.save(update_fields=["payment_status", "refunded_amount", "updated_at"])
    elif order.payment_status in (Order.PaymentStatus.PENDING, Order.PaymentStatus.FAILED) and order.credit_used and all_cancelled \
            and order.refunded_amount < order.credit_used:
        add_credit(order.user, order.credit_used, CreditEntry.Kind.RESTORED, order, f"Commande #{order.id} annulée avant paiement")
        order.refunded_amount = order.credit_used
        order.save(update_fields=["refunded_amount", "updated_at"])


def _sync_order_status(order, changed_by):
    """Déduit le statut de la commande globale à partir de ses sous-commandes."""
    statuses = set(order.vendor_orders.values_list("status", flat=True))
    terminal = {VendorOrder.Status.DELIVERED, VendorOrder.Status.CANCELLED}

    if statuses == {VendorOrder.Status.CANCELLED}:
        new_status = Order.Status.CANCELLED
    elif statuses <= terminal:
        new_status = Order.Status.DELIVERED
    elif statuses == {VendorOrder.Status.PENDING}:
        new_status = Order.Status.PENDING
    else:
        new_status = Order.Status.PROCESSING

    if new_status != order.status:
        order.status = new_status
        fields = ["status", "updated_at"]
        if new_status == Order.Status.DELIVERED and order.payment_method == Order.PaymentMethod.CASH_ON_DELIVERY:
            order.payment_status = Order.PaymentStatus.PAID
            fields.append("payment_status")
        order.save(update_fields=fields)
        OrderStatusHistory.objects.create(order=order, status=new_status, changed_by=changed_by)
        if new_status == Order.Status.DELIVERED:
            _reward_referrer(order)


def _reward_referrer(order):
    """Première commande livrée d'un filleul : son parrain reçoit un bon de réduction."""
    user = order.user
    if not user.referred_by_id:
        return
    if Order.objects.filter(user=user, status=Order.Status.DELIVERED).exclude(pk=order.pk).exists():
        return
    coupon = create_referral_coupon(user.referred_by, f"Parrainage de {user.name}")
    notify(user.referred_by, "promo", "Votre filleul a commandé 🎁",
           f"Merci ! Voici votre bon de {gnf(coupon.value)} : {coupon.code}", "/compte")


@transaction.atomic
def cancel_order(order, user):
    """Annulation par le client : annule toutes les parties qui ne sont pas encore en préparation."""
    order = Order.objects.select_for_update().get(pk=order.pk)
    cancellable = list(order.vendor_orders.select_related("vendor__user").filter(status__in=CANCELLABLE_VENDOR_STATUSES))
    if not cancellable:
        raise OrderError("Cette commande ne peut plus être annulée : elle est déjà en préparation ou livrée.")

    for vendor_order in cancellable:
        vendor_order.status = VendorOrder.Status.CANCELLED
        vendor_order.save(update_fields=["status", "updated_at"])
        _restore_stock(vendor_order)
        if is_visible_to_vendor(order):
            notify(vendor_order.vendor.user, "order", f"Commande #{order.id} annulée",
                   "Le client a annulé sa commande.", f"/vendeur/commandes/{vendor_order.id}", whatsapp=False)

    _refund(order, cancellable, "annulée à votre demande")
    _sync_order_status(order, user)
    return order


@transaction.atomic
def update_vendor_order_status(vendor_order, new_status, user, code_checked=False):
    """
    Fait avancer une sous-commande. Passer en « livrée » exige d'avoir vérifié le code
    du client avec check_delivery_code() (code_checked=True), que ce soit la boutique ou le livreur.
    """
    vendor_order = VendorOrder.objects.select_for_update().select_related("order__user").get(pk=vendor_order.pk)
    order = vendor_order.order
    if not is_visible_to_vendor(order):
        raise OrderError("Cette commande attend encore le paiement du client.")
    if new_status not in allowed_vendor_transitions(vendor_order):
        raise OrderError(
            f"Impossible de passer de « {vendor_order.get_status_display()} » "
            f"à « {VendorOrder.Status(new_status).label} »."
        )

    if new_status == VendorOrder.Status.DELIVERED and not code_checked:
        raise OrderError("Saisissez le code de livraison donné par le client.")
    home = order.delivery_method == Order.DeliveryMethod.HOME
    if new_status == VendorOrder.Status.READY and home and not vendor_order.vendor.has_pickup_address:
        raise OrderError(
            "Renseignez d'abord l'adresse de retrait de votre boutique (Ma boutique → Adresse de retrait) : "
            "le livreur doit savoir où venir chercher le colis."
        )

    vendor_order.status = new_status
    fields = ["status", "updated_at"]
    now = timezone.now()
    if new_status == VendorOrder.Status.PREPARING:
        vendor_order.accepted_at = now
        fields.append("accepted_at")
    if new_status == VendorOrder.Status.READY:
        vendor_order.ready_at = now
        vendor_order.delivery_code = new_delivery_code()
        vendor_order.delivery_code_attempts = 0
        fields += ["ready_at", "delivery_code", "delivery_code_attempts"]
    if new_status == VendorOrder.Status.DELIVERED:
        vendor_order.delivered_at = now
        fields.append("delivered_at")
    vendor_order.save(update_fields=fields)

    if new_status == VendorOrder.Status.CANCELLED:
        _restore_stock(vendor_order)
        _refund(order, [vendor_order], f"{vendor_order.vendor.shop_name} a annulé sa partie")
    if new_status == VendorOrder.Status.READY and home:
        # Course créée : c'est la boutique qui choisit ensuite son livreur (ou livre elle-même)
        Delivery.objects.get_or_create(vendor_order=vendor_order, defaults={"fee": vendor_order.delivery_fee})
    if new_status == VendorOrder.Status.DELIVERED:
        # La boutique a livré elle-même : la course est retirée (et le livreur prévenu s'il l'avait)
        for delivery in Delivery.objects.filter(
            vendor_order=vendor_order, status__in=[Delivery.Status.OPEN, Delivery.Status.OFFERED, Delivery.Status.ASSIGNED]
        ).select_related("rider__user"):
            delivery.status = Delivery.Status.CANCELLED
            delivery.save(update_fields=["status", "updated_at"])
            if delivery.rider:
                notify(delivery.rider.user, "delivery", f"Course annulée – commande #{order.id}",
                       f"{vendor_order.vendor.shop_name} a livré le colis elle-même.", "/livreur")

    OrderStatusHistory.objects.create(
        order=order, status=new_status, changed_by=user,
        note=f"{vendor_order.vendor} : {vendor_order.get_status_display()}",
    )
    _sync_order_status(order, user)

    messages = {
        VendorOrder.Status.PREPARING: "a accepté votre commande et la prépare.",
        VendorOrder.Status.READY: "a préparé votre colis, il part bientôt.",
        VendorOrder.Status.DELIVERED: "indique que votre colis est livré.",
        VendorOrder.Status.CANCELLED: "a annulé sa partie de votre commande.",
    }
    if new_status in messages:
        body = f"{vendor_order.vendor.shop_name} {messages[new_status]}"
        if new_status == VendorOrder.Status.READY:
            body += (
                f" Votre code de livraison : {vendor_order.delivery_code}."
                " Donnez-le uniquement à la personne qui vous remet le colis."
            )
        if new_status == VendorOrder.Status.DELIVERED and order.payment_method in ONLINE_METHODS:
            body += " Confirmez la réception pour que la boutique soit payée."
        notify(order.user, "order", f"Commande #{order.id}", body, f"/commandes/{order.id}")

    if new_status == VendorOrder.Status.DELIVERED and order.payment_method not in ONLINE_METHODS:
        from apps.wallet.services import release_funds

        transaction.on_commit(lambda: release_funds(vendor_order))
    return vendor_order


@transaction.atomic
def confirm_reception(order, user):
    """Le client confirme avoir reçu ses colis : le paiement en séquestre est versé aux boutiques."""
    from apps.wallet.services import release_funds

    delivered = list(
        order.vendor_orders.filter(status=VendorOrder.Status.DELIVERED, received_at__isnull=True)
    )
    if not delivered:
        raise OrderError("Aucun colis livré en attente de confirmation.")
    for vendor_order in delivered:
        vendor_order.received_at = timezone.now()
        vendor_order.save(update_fields=["received_at", "updated_at"])
        transaction.on_commit(lambda vo=vendor_order: release_funds(vo))
    OrderStatusHistory.objects.create(order=order, status=order.status, changed_by=user, note="Réception confirmée")
    return order


@transaction.atomic
def expire_unpaid_order(order):
    """Commande payable en ligne restée impayée : annulée et stock remis."""
    order = Order.objects.select_for_update().get(pk=order.pk)
    if order.payment_status != Order.PaymentStatus.PENDING or order.status == Order.Status.CANCELLED:
        return False
    for vendor_order in order.vendor_orders.exclude(status=VendorOrder.Status.CANCELLED):
        vendor_order.status = VendorOrder.Status.CANCELLED
        vendor_order.save(update_fields=["status", "updated_at"])
        _restore_stock(vendor_order)
    order.payment_status = Order.PaymentStatus.FAILED
    order.save(update_fields=["payment_status", "updated_at"])
    _refund(order, [], "")  # rend l'avoir engagé, s'il y en a
    OrderStatusHistory.objects.create(order=order, status=Order.Status.CANCELLED, note="Paiement non reçu à temps")
    _sync_order_status(order, None)
    return True
