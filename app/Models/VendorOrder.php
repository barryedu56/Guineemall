<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'vendor_id',
        'total_amount',
        'status',
    ];

    /**
     * Relation : la sous-commande appartient à une commande principale
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relation : la sous-commande appartient à un vendeur
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relation : les articles de cette sous-commande
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Vérifie si la sous-commande peut être annulée
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Vérifie si la sous-commande peut être confirmée
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifie si la sous-commande peut être marquée comme en préparation
     */
    public function canBePreparing(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Vérifie si la sous-commande peut être marquée comme prête
     */
    public function canBeReady(): bool
    {
        return $this->status === 'preparing';
    }

    /**
     * Vérifie si la sous-commande peut être marquée comme livrée
     */
    public function canBeDelivered(): bool
    {
        return $this->status === 'ready';
    }

    /**
     * Transitions de statut valides
     */
    public static function getValidTransitions(): array
    {
        return [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready'],
            'ready' => ['delivered'],
            'delivered' => [], // Terminal
            'cancelled' => [], // Terminal
        ];
    }

    /**
     * Vérifie si une transition de statut est valide
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = self::getValidTransitions();
        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }
}
