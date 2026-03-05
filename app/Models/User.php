<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'phone',
        'delivery_address',
        'address',
    ];

    /**
 * Relation : un utilisateur vendeur a une boutique
 */
public function vendor()
{
    return $this->hasOne(Vendor::class);
}


public function orders()
{
    return $this->hasMany(Order::class);
}

public function cartItems()
{
    return $this->hasMany(Cart::class);
}

public function favorites()
{
    return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
}

/**
 * Obtenir le nombre d'articles dans le panier
 */
public function getCartCountAttribute()
{
    return $this->cartItems()->count();
}

public function adminLogs()
{
    return $this->hasMany(AdminLog::class, 'admin_id');
}

/**
 * Relation : un utilisateur vendeur a plusieurs commandes vendeur
 */
public function vendorOrders()
{
    return $this->hasManyThrough(
        VendorOrder::class,
        Vendor::class,
        'user_id',
        'vendor_id',
        'id',
        'id'
    );
}



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
