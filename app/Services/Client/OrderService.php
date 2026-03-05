<?php

namespace App\Services\Client;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\VendorOrder;
use App\Models\Cart;
use App\Models\Product;
use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Exceptions\OrderStatusException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Crée une commande à partir du panier avec transaction et verrouillage stock
     */
    public function createOrderFromCart(int $userId, ?string $paymentMethod = null, ?string $paymentReference = null): Order
    {
        return DB::transaction(function () use ($userId, $paymentMethod, $paymentReference) {
            try {
                // Récupérer les articles du panier avec verrouillage pessimiste des produits
                $cartItems = Cart::forUser($userId)
                                ->with(['product' => function ($query) {
                                    $query->lockForUpdate(); // Verrouillage pessimiste
                                }])
                                ->get();

                if ($cartItems->isEmpty()) {
                    throw new OrderException('Votre panier est vide.');
                }

                // Vérification stricte des stocks avec verrouillage
                foreach ($cartItems as $item) {
                    $productName = $item->product ? $item->product->name : 'Inconnu';
                    
                    if (!$item->product || $item->product->status !== 'active') {
                        throw new OrderException("Le produit '{$productName}' n'est plus disponible.");
                    }

                    if ($item->product->stock < $item->quantity) {
                        throw new StockException(
                            "Stock insuffisant pour '{$productName}'. " .
                            "Demandé: {$item->quantity}, Disponible: {$item->product->stock}"
                        );
                    }
                }

                // Regrouper les articles par vendeur pour les sous-commandes
                $itemsByVendor = $cartItems->groupBy(function ($item) {
                    return $item->product->vendor_id;
                });

                // Calculer le total général
                $totalAmount = $cartItems->sum(function ($item) {
                    return $item->quantity * $item->product->price;
                });

                // Créer la commande principale
                $order = Order::create([
                    'user_id' => $userId,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'payment_reference' => $paymentReference,
                    'payment_status' => 'pending',
                ]);

                // Créer les sous-commandes par vendeur
                foreach ($itemsByVendor as $vendorId => $vendorItems) {
                    $vendorTotal = $vendorItems->sum(function ($item) {
                        return $item->quantity * $item->product->price;
                    });

                    $vendorOrder = VendorOrder::create([
                        'order_id' => $order->id,
                        'vendor_id' => $vendorId,
                        'total_amount' => $vendorTotal,
                        'status' => 'pending',
                    ]);

                    // Créer les lignes de commande pour ce vendeur
                    foreach ($vendorItems as $item) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'vendor_order_id' => $vendorOrder->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $item->product->price, // Prix figé
                        ]);

                        // Décrémenter le stock (toujours dans la transaction)
                        $item->product->decrement('stock', $item->quantity);
                    }
                }

                // Vider le panier uniquement si tout a réussi
                Cart::forUser($userId)->delete();

                Log::info("Commande #{$order->id} créée avec succès pour l'utilisateur #{$userId}");

                DB::afterCommit(function () use ($order) {
                    event(new \App\Events\OrderCreated($order));
                });

                return $order;

            } catch (\Exception $e) {
                Log::error("Erreur lors de la création de commande pour l'utilisateur #{$userId}: " . $e->getMessage());
                throw $e; // La transaction sera automatiquement rollbackée
            }
        });
    }

    /**
     * Annule une commande avec restauration des stocks et gestion des sous-commandes
     */
    public function cancelOrder(Order $order): void
    {
        if (!$this->canUserCancelOrder($order)) {
            throw new OrderStatusException('Cette commande ne peut plus être annulée.');
        }

        DB::transaction(function () use ($order) {
            try {
                // Annuler toutes les sous-commandes
                foreach ($order->vendorOrders as $vendorOrder) {
                    if ($vendorOrder->canBeCancelled()) {
                        $vendorOrder->update(['status' => 'cancelled']);
                    }
                }

                // Restaurer les stocks
                foreach ($order->orderItems as $item) {
                    $item->product->increment('stock', $item->quantity);
                }

                // Mettre à jour le statut de la commande principale
                $order->update(['status' => 'cancelled']);

                Log::info("Commande #{$order->id} annulée avec succès");

            } catch (\Exception $e) {
                Log::error("Erreur lors de l'annulation de la commande #{$order->id}: " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Met à jour le statut d'une commande avec validation des transitions
     */
    public function updateOrderStatus(Order $order, string $newStatus): void
    {
        if (!$this->isValidStatusTransition($order->status, $newStatus)) {
            throw new OrderStatusException(
                "Transition de statut invalide: de '{$order->status}' vers '{$newStatus}'"
            );
        }

        $order->update(['status' => $newStatus]);
        
        Log::info("Commande #{$order->id}: statut mis à jour de '{$order->getOriginal('status')}' vers '{$newStatus}'");
    }

    /**
     * Met à jour le statut d'une sous-commande vendeur
     */
    public function updateVendorOrderStatus(VendorOrder $vendorOrder, string $newStatus): void
    {
        if (!$vendorOrder->canTransitionTo($newStatus)) {
            throw new OrderStatusException(
                "Transition de statut invalide pour la sous-commande: de '{$vendorOrder->status}' vers '{$newStatus}'"
            );
        }

        DB::transaction(function () use ($vendorOrder, $newStatus) {
            $vendorOrder->update(['status' => $newStatus]);

            // Mettre à jour le statut de la commande principale si nécessaire
            $this->updateMainOrderStatusFromVendorOrders($vendorOrder->order);
        });
    }

    /**
     * Vérifie si un utilisateur peut annuler une commande
     */
    public function canUserCancelOrder(Order $order): bool
    {
        return in_array($order->status, ['pending', 'processing']);
    }

    /**
     * Vérifie si une commande est modifiable par le client
     */
    public function isOrderModifiableByClient(Order $order): bool
    {
        return in_array($order->status, ['pending']);
    }

    /**
     * Transitions de statut valides pour les commandes principales
     */
    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['delivered', 'cancelled'],
            'delivered' => [], // Terminal
            'cancelled' => [], // Terminal
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    /**
     * Met à jour le statut de la commande principale en fonction des sous-commandes
     */
    private function updateMainOrderStatusFromVendorOrders(Order $order): void
    {
        $vendorOrders = $order->vendorOrders;
        
        if ($vendorOrders->isEmpty()) {
            return;
        }

        $allCancelled = $vendorOrders->every(fn($vo) => $vo->status === 'cancelled');
        $allDelivered = $vendorOrders->every(fn($vo) => $vo->status === 'delivered');
        $anyProcessing = $vendorOrders->contains(fn($vo) => in_array($vo->status, ['confirmed', 'preparing', 'ready']));

        if ($allCancelled) {
            $order->update(['status' => 'cancelled']);
        } elseif ($allDelivered) {
            $order->update(['status' => 'delivered']);
        } elseif ($anyProcessing) {
            $order->update(['status' => 'processing']);
        }
    }

    /**
     * Vérifie si un client peut passer commande
     */
    public function canUserOrder(int $userId): array
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user) {
            return ['can_order' => false, 'message' => 'Utilisateur non trouvé.'];
        }

        if (!$user->phone) {
            return ['can_order' => false, 'message' => 'Veuillez ajouter votre numéro de téléphone.'];
        }

        if (!$user->delivery_address) {
            return ['can_order' => false, 'message' => 'Veuillez ajouter votre adresse de livraison.'];
        }

        return ['can_order' => true, 'message' => 'OK'];
    }
}
