<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'quantity',
    ];

    /**
     * Relation : le panier appartient à un client
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : le panier contient un produit
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calcule le sous-total pour cet article du panier
     */
    public function getSubtotalAttribute()
    {
        if (! $this->relationLoaded('product')) {
            $this->load('product');
        }

        if (! $this->product) {
            return 0;
        }

        return $this->quantity * $this->product->price;
    }

    /**
     * Scope : récupérer les articles du panier d'un client
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope : récupérer les articles du panier d'une session invitée
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->whereNull('user_id')
                     ->where('session_id', $sessionId);
    }

    /**
     * Scope : récupérer uniquement les articles avec des produits disponibles
     */
    public function scopeWithAvailableProducts($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('status', 'active')
              ->where('stock', '>', 0);
        });
    }
}
