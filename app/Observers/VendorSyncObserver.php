<?php

namespace App\Observers;

use App\Models\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class VendorSyncObserver
{
    /**
     * Handle the Vendor "created" event.
     */
    public function created(Vendor $vendor): void
    {
        Log::info('Vendor created', [
            'vendor_id' => $vendor->id,
            'user_id' => $vendor->user_id,
            'shop_name' => $vendor->shop_name
        ]);
        
        // S'assurer que l'utilisateur a le rôle vendeur
        if ($vendor->user) {
            $vendor->user->update(['role' => 'vendeur']);
        }
    }

    /**
     * Handle the Vendor "deleted" event.
     */
    public function deleted(Vendor $vendor): void
    {
        Log::info('Vendor deleted', [
            'vendor_id' => $vendor->id,
            'user_id' => $vendor->user_id
        ]);
    }

    /**
     * Handle the Vendor "force deleted" event.
     */
    public function forceDeleted(Vendor $vendor): void
    {
        Log::info('Vendor force deleted', [
            'vendor_id' => $vendor->id,
            'user_id' => $vendor->user_id
        ]);
        
        // Optionnel: conserver le rôle vendeur sur l'utilisateur même si le vendor est supprimé
        // L'utilisateur pourra créer un nouveau vendor si nécessaire
    }

    /**
     * Handle the Vendor "restored" event.
     */
    public function restored(Vendor $vendor): void
    {
        Log::info('Vendor restored', [
            'vendor_id' => $vendor->id,
            'user_id' => $vendor->user_id
        ]);
    }
}
