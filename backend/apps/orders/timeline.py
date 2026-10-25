"""Étapes d'une sous-commande, identiques pour le client, la boutique et le livreur."""

from .models import Order, VendorOrder

STATUS_RANK = {
    VendorOrder.Status.PENDING: 0,
    VendorOrder.Status.CONFIRMED: 0,
    VendorOrder.Status.PREPARING: 1,
    VendorOrder.Status.READY: 2,
    VendorOrder.Status.DELIVERED: 3,
}
DELIVERY_RANK = {"assigned": 1, "picked_up": 2, "delivered": 3}


def vendor_order_timeline(vo):
    order = vo.order
    delivery = getattr(vo, "delivery", None)
    if delivery is not None and delivery.status == "cancelled":
        delivery = None
    home = order.delivery_method == Order.DeliveryMethod.HOME
    online = order.payment_method != Order.PaymentMethod.CASH_ON_DELIVERY
    rank = STATUS_RANK.get(vo.status, 0)
    courier = DELIVERY_RANK.get(delivery.status, 0) if delivery else 0
    if rank == 3 and delivery is None:
        courier = 3  # livrée par la boutique elle-même
    rider = delivery.rider.user.name if delivery and delivery.rider_id and courier else None

    # (clé, libellé, date, faite) ; « faite » d'après le statut, pour les commandes d'avant les dates d'étape
    steps = [
        ("received", "Commande reçue par la boutique", vo.created_at, True),
        ("accepted", "Acceptée, en préparation", vo.accepted_at, rank >= 1),
        ("ready", "Colis prêt", vo.ready_at, rank >= 2),
    ]
    if home and not (rank == 3 and delivery is None):
        steps += [
            ("rider", f"Livreur confirmé : {rider}" if rider else "Livreur confirmé", delivery and delivery.accepted_at, courier >= 1),
            ("picked_up", "Colis récupéré par le livreur", delivery and delivery.picked_up_at, courier >= 2),
        ]
    steps.append(("delivered", "Livré au client" if home else "Déposé au point relais et remis", vo.delivered_at, rank >= 3))
    if not online and delivery is not None and delivery.rider_id:
        # Paiement à la livraison : le livreur remet l'argent du client à la boutique
        steps.append(("cash", "Argent remis à la boutique", delivery.cash_handed_at, bool(delivery.cash_handed_at)))
    if online:
        paid = vo.received_at or vo.funds_released_at
        steps.append(("paid_out", "Réception confirmée, boutique payée", paid, bool(paid)))

    if vo.status == VendorOrder.Status.CANCELLED:
        done = [{"key": k, "label": label, "at": at, "done": True} for k, label, at, _ in steps if at]
        return done + [{"key": "cancelled", "label": "Annulée", "at": vo.updated_at, "done": True}]
    return [{"key": k, "label": label, "at": at, "done": bool(done or at)} for k, label, at, done in steps]
