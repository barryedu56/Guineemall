<?php

namespace App\Models;

use App\Traits\HasImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, HasImage, SoftDeletes;

    protected $fillable = [
        'user_id',
        'shop_name',
        'description',
        'logo',
        'status',
    ];

    /**
     * Relation : un vendeur appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : un vendeur a plusieurs produits
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Relation : un vendeur a plusieurs commandes
     */
    public function vendorOrders()
    {
        return $this->hasMany(VendorOrder::class);
    }

    /**
     * Vérifie si le vendeur est approuvé
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Configuration du trait HasImage pour les vendeurs
     * Champ: logo
     * Dossier: uploads/vendors
     */
    protected function getImageField(): string
    {
        return 'logo';
    }
    
    protected function getImageStoragePath(): string
    {
        return 'uploads/vendors';
    }
}
