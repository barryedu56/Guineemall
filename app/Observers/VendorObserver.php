<?php

namespace App\Observers;

use App\Models\Vendor;

class VendorObserver
{
    public function deleting(Vendor $vendor): void
    {
        if (method_exists($vendor, 'deleteImage')) {
            $vendor->deleteImage();
        }
    }
}
