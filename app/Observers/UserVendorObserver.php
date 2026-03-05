<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Vendor;

class UserVendorObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Désactiver la création automatique pour éviter les doublons
        // lors de la création manuelle via l'admin
        return;
        
        // Si l'utilisateur est créé avec le rôle vendeur, créer automatiquement son vendor
        if ($user->role === 'vendeur') {
            // Vérifier si le vendor n'existe pas déjà
            if (!$user->vendor) {
                Log::info('Auto-creating vendor for user', ['user_id' => $user->id, 'email' => $user->email]);
                
                try {
                    Vendor::create([
                        'user_id' => $user->id,
                        'shop_name' => $user->name . ' Boutique',
                        'description' => 'Boutique de ' . $user->name,
                        'status' => 'pending',
                    ]);
                    
                    Log::info('Vendor successfully created for user', ['user_id' => $user->id]);
                } catch (\Exception $e) {
                    Log::error('Failed to create vendor for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Si le rôle change à vendeur, créer le vendor
        if ($user->isDirty('role') && $user->role === 'vendeur' && !$user->vendor) {
            Log::info('Creating vendor for user role change', ['user_id' => $user->id, 'email' => $user->email]);
            
            Vendor::create([
                'user_id' => $user->id,
                'shop_name' => $user->name . ' Boutique',
                'description' => 'Boutique de ' . $user->name,
                'status' => 'pending',
            ]);
        }
        
        // Si le rôle change de vendeur à autre chose, supprimer ou désactiver le vendor
        if ($user->isDirty('role') && $user->role !== 'vendeur' && $user->vendor) {
            Log::info('Removing vendor for user role change', ['user_id' => $user->id, 'email' => $user->email]);
            
            // Soft delete du vendor pour conserver l'historique
            $user->vendor->delete();
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Si l'utilisateur supprimé avait un vendor, le supprimer aussi
        if ($user->vendor) {
            Log::info('Deleting vendor for deleted user', ['user_id' => $user->id, 'email' => $user->email]);
            
            try {
                $user->vendor->forceDelete();
                Log::info('Vendor successfully deleted for user', ['user_id' => $user->id]);
            } catch (\Exception $e) {
                Log::error('Failed to delete vendor for user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
