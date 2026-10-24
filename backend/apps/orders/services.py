"""
Logique métier des commandes (reprise de App\\Services\\Client\\OrderService en Laravel).

Différences avec l'ancienne version :
- le stock est remis quand un vendeur annule sa partie de commande ;
- quand le client annule, seules les parties encore annulables le sont,
  et le stock n'est remis que pour celles-ci ;
- une commande dont toutes les parties sont terminées (livrées ou annulées,
  avec au moins une livrée) passe en « livrée » au lieu de rester bloquée.
"""

from collections import defaultdict

from django.db import transaction
from django.db.models import F

from apps.catalog.models import Product
from apps.vendors.models import Vendor

from .models import CartItem, Order, OrderItem, OrderStatusHistory, VendorOrder


class OrderError(Exception):
    """Erreur métier affichable telle quelle au client."""


VENDOR_ORDER_TRANSITIONS = {
    VendorOrder.Status.PENDING: [VendorOrder.Status.CONFIRMED, VendorOrder.Status.CANCELLED],
    VendorOrder.Status.CONFIRMED: [VendorOrder.Status.PREPARING, VendorOrder.Status.CANCELLED],
    VendorOrder.Status.PREPARING: [VendorOrder.Status.READY],
    VendorOrder.Status.READY: [VendorOrder.Status.DELIVERED],
    VendorOrder.Status.DELIVERED: [],
    VendorOrder.Status.CANCELLED: [],
}

CANCELLABLE_VENDOR_STATUSES = [VendorOrder.Status.PENDING, VendorOrder.Status.CONFIRMED]


def allowed_vendor_transitions(vendor_order):
    return VENDOR_ORDER_TRANSITIONS[vendor_order.status]


@transaction.atomic
def checkout(user, address, payment_method):
    """Transforme le panier en commande, découpée en une sous-commande par boutique."""
    cart = list(CartItem.objects.filter(user=user).select_related("product__vendor"))
    if not cart:
        raise OrderError("Votre panier est vide.")

    # Verrouille les produits pour éviter de vendre deux fois le dernier article
    products = Product.objects.select_for_update().in_bulk([item.product_id for item in cart])

    by_vendor = defaultdict(list)
    for item in cart:
        product = products[item.product_id]
        if (
            product.is_deleted
            or product.status != Product.Status.ACTIVE
            or item.product.vendor.status != Vendor.Status.APPROVED
        ):
            raise OrderError(f"Le produit « {product.name} » n'est plus disponible.")
        if product.stock < item.quantity:
            raise OrderError(
                f"Stock insuffisant pour « {product.name} » : "
                f"demandé {item.quantity}, disponible {product.stock}."
            )
        by_vendor[product.vendor_id].append((product, item.quantity))

    order = Order.objects.create(
        user=user,
        total_amount=sum(p.price * qty for lines in by_vendor.values() for p, qty in lines),
        payment_method=payment_method,
        delivery_city=address.city,
        delivery_commune=address.commune,
        delivery_quartier=address.quartier,
        delivery_landmark=address.landmark,
        delivery_latitude=address.latitude,
        delivery_longitude=address.longitude,
        delivery_phone=address.contact_phone,
    )

    for vendor_id, lines in by_vendor.items():
        vendor_order = VendorOrder.objects.create(
            order=order, vendor_id=vendor_id, total_amount=sum(p.price * qty for p, qty in lines)
        )
        OrderItem.objects.bulk_create(
            OrderItem(
                order=order,
                vendor_order=vendor_order,
                product=product,
                product_name=product.name,
                unit_price=product.price,
                quantity=qty,
            )
            for product, qty in lines
        )
        for product, qty in lines:
            Product.objects.filter(pk=product.pk).update(stock=F("stock") - qty)

    OrderStatusHistory.objects.create(order=order, status=order.status, changed_by=user, note="Commande passée")
    CartItem.objects.filter(user=user).delete()
    return order


def _restore_stock(vendor_order):
    for item in vendor_order.items.filter(product__isnull=False):
        Product.objects.filter(pk=item.product_id).update(stock=F("stock") + item.quantity)


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
        order.save(update_fields=["status", "updated_at"])
        OrderStatusHistory.objects.create(order=order, status=new_status, changed_by=changed_by)


@transaction.atomic
def cancel_order(order, user):
    """Annulation par le client : annule toutes les parties qui ne sont pas encore en préparation."""
    order = Order.objects.select_for_update().get(pk=order.pk)
    cancellable = list(order.vendor_orders.filter(status__in=CANCELLABLE_VENDOR_STATUSES))
    if not cancellable:
        raise OrderError("Cette commande ne peut plus être annulée : elle est déjà en préparation ou livrée.")

    for vendor_order in cancellable:
        vendor_order.status = VendorOrder.Status.CANCELLED
        vendor_order.save(update_fields=["status", "updated_at"])
        _restore_stock(vendor_order)

    _sync_order_status(order, user)
    return order


@transaction.atomic
def update_vendor_order_status(vendor_order, new_status, user):
    vendor_order = VendorOrder.objects.select_for_update().get(pk=vendor_order.pk)
    if new_status not in allowed_vendor_transitions(vendor_order):
        raise OrderError(
            f"Impossible de passer de « {vendor_order.get_status_display()} » "
            f"à « {VendorOrder.Status(new_status).label} »."
        )

    vendor_order.status = new_status
    vendor_order.save(update_fields=["status", "updated_at"])
    if new_status == VendorOrder.Status.CANCELLED:
        _restore_stock(vendor_order)

    OrderStatusHistory.objects.create(
        order=vendor_order.order,
        status=new_status,
        changed_by=user,
        note=f"{vendor_order.vendor} : {vendor_order.get_status_display()}",
    )
    _sync_order_status(vendor_order.order, user)
    return vendor_order
