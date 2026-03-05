<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'vendeur' && $user->vendor) {
            return $product->vendor_id === $user->vendor->id;
        }

        return false;
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }
}
