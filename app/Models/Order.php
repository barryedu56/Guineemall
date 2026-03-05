<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'payment_method',
        'payment_reference',
        'payment_status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_method' => 'string',
        'payment_reference' => 'string',
        'payment_status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendorOrders()
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Alias pour orderItems pour compatibilité
     */
    public function items()
    {
        return $this->orderItems();
    }

    /**
     * Vérifie si la commande peut être annulée par le client
     */
    public function canBeCancelledByClient(): bool
    {
        return in_array($this->public_status_key, ['pending', 'processing']);
    }

    /**
     * Vérifie si la commande est modifiable par le client
     */
    public function isModifiableByClient(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifie si la commande est terminée (livrée ou annulée)
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['delivered', 'cancelled']);
    }

    /**
     * Statut public calculé à partir des commandes vendeurs
     */
    public function getPublicStatusKeyAttribute(): string
    {
        if (! $this->relationLoaded('vendorOrders')) {
            $this->load('vendorOrders');
        }

        $vendorStatuses = $this->vendorOrders->pluck('status');
        $allDelivered = $vendorStatuses->isNotEmpty() && $vendorStatuses->every(fn ($s) => $s === 'delivered');
        $allCancelled = $vendorStatuses->isNotEmpty() && $vendorStatuses->every(fn ($s) => $s === 'cancelled');
        $hasPreparing = $vendorStatuses->contains(fn ($s) => in_array($s, ['preparing', 'ready']));
        $hasConfirmed = $vendorStatuses->contains('confirmed');

        if ($allDelivered) {
            return 'delivered';
        }

        if ($allCancelled) {
            return 'cancelled';
        }

        if ($hasPreparing) {
            return 'processing';
        }

        if ($hasConfirmed) {
            return 'confirmed';
        }

        return $this->status;
    }

    /**
     * Libellé public calculé à partir du statut
     */
    public function getPublicStatusLabelAttribute(): string
    {
        return match ($this->public_status_key) {
            'pending' => 'En attente',
            'processing' => 'En préparation',
            'confirmed' => 'Confirmée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            default => $this->public_status_key,
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'orange_money' => 'Orange Money',
            'mtn_money' => 'MTN Money',
            'cash' => 'Paiement à la livraison',
            default => 'Non renseigné',
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            'pending' => 'En attente de paiement',
            'paid' => 'Payée',
            'failed' => 'Échec de paiement',
            default => $this->payment_status ?? 'En attente',
        };
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
