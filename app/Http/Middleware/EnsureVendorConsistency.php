<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsureVendorConsistency
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Pour les routes de création/modification de vendeurs
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $this->ensureVendorUserConsistency();
        }
        
        return $next($request);
    }
    
    /**
     * Garantir la cohérence entre users et vendors
     */
    private function ensureVendorUserConsistency()
    {
        // Nettoyer les vendors sans user valide
        \App\Models\Vendor::whereNull('user_id')
            ->orWhereDoesntHave('user')
            ->each(function($vendor) {
                Log::warning('Vendor orphan detected and cleaned', ['vendor_id' => $vendor->id]);
                $vendor->forceDelete();
            });
        
        // Créer des vendors pour les users vendeur qui n'en ont pas
        \App\Models\User::where('role', 'vendeur')
            ->whereDoesntHave('vendor')
            ->each(function($user) {
                Log::info('Creating missing vendor for user', ['user_id' => $user->id, 'email' => $user->email]);
                
                \App\Models\Vendor::create([
                    'user_id' => $user->id,
                    'shop_name' => $user->name . ' Boutique',
                    'description' => 'Boutique de ' . $user->name,
                    'status' => 'pending',
                ]);
            });
    }
}
