<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOrder;

class VendorOrderPolicy
{
    public function view(User $user, VendorOrder $vendorOrder): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->vendor && $vendorOrder->vendor_id === $user->vendor->id;
    }

    public function update(User $user, VendorOrder $vendorOrder): bool
    {
        return $this->view($user, $vendorOrder);
    }
}
