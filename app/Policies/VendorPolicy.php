<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

class VendorPolicy
{
    public function view(User $user, Vendor $vendor): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->vendor && $user->vendor->id === $vendor->id;
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->view($user, $vendor);
    }
}
