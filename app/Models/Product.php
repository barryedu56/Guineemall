<?php

namespace App\Models;

use App\Traits\HasImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, HasImage, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'image',
        'status',
    ];

    /**
     * Relation : un produit appartient à une catégorie
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relation : un produit appartient à un vendeur
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relation : un produit peut être dans plusieurs commandes
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Vérifie si le produit est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Vérifie si le produit est en stock
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Vérifie si le stock est bas
     */
    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= 5;
    }

    /**
     * Vérifie si le produit est en rupture de stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Configuration du trait HasImage pour les produits
     * Champ: image
     * Dossier: uploads/products
     */
    protected function getImageField(): string
    {
        return 'image';
    }
    
    protected function getImageStoragePath(): string
    {
        return 'uploads/products';
    }

    /**
     * Obtenir le prix formaté
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ') . ' GNF';
    }

    /**
     * Scope pour les produits actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les produits en stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
}
