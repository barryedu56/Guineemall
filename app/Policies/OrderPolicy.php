<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'client') {
            return $order->user_id === $user->id;
        }

        if ($user->role === 'vendeur' && $user->vendor) {
            return $order->vendorOrders()
                ->where('vendor_id', $user->vendor->id)
                ->exists();
        }

        return false;
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->role !== 'client') {
            return false;
        }

        return $order->user_id === $user->id && $order->canBeCancelledByClient();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }
}
