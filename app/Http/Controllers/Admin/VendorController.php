<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Notifications\VendorApproved;
use App\Jobs\SendVendorNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    /**
     * Afficher le formulaire de création d'un vendeur
     */
    public function create(): View
    {
        return view('admin.vendors.create');
    }

    /**
     * Créer un nouveau vendeur
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'shop_description' => 'nullable|string|max:1000',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            // Créer l'utilisateur vendeur
            $user = \App\Models\User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => bcrypt('password123'), // Mot de passe par défaut
                'role' => 'vendeur',
                'email_verified_at' => now(),
            ]);

            // Créer le vendeur
            $vendor = Vendor::create([
                'user_id' => $user->id,
                'shop_name' => $validated['shop_name'],
                'shop_description' => $validated['shop_description'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'status' => 'pending',
            ]);

            return redirect()
                ->route('admin.vendors.show', $vendor)
                ->with('success', 'Vendeur créé avec succès.');

        } catch (\Exception $e) {
            Log::error('Error creating vendor', ['error' => $e->getMessage()]);
            
            return redirect()
                ->route('admin.vendors.create')
                ->with('error', 'Erreur lors de la création du vendeur.')
                ->withInput();
        }
    }

    /**
     * Liste des vendeurs (admin)
     * - pagination
     * - filtre par statut
     * - gère correctement les soft deletes et relations
     */
    public function index(Request $request): View
    {
        try {
            // Diagnostic rapide
            $this->diagnoseVendorConsistency();
            
            $query = Vendor::with('user')
                ->whereHas('user', function($q) {
                    $q->where('role', 'vendeur');
                });

            // Filtrer par statut (pending / approved / suspended)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $vendors = $query
                ->latest()
                ->paginate(10)
                ->withQueryString();

            return view('admin.vendors.index', compact('vendors'));
            
        } catch (\Exception $e) {
            Log::error('Error in vendor index', ['error' => $e->getMessage()]);
            
            // Fallback: requête simple sans filtres
            $vendors = Vendor::with('user')->latest()->paginate(10);
            
            return view('admin.vendors.index', compact('vendors'))
                ->with('warning', 'Certains filtres peuvent ne pas fonctionner correctement.');
        }
    }
    
    /**
     * Diagnostic de la cohérence des vendors
     */
    private function diagnoseVendorConsistency(): void
    {
        $orphanVendors = Vendor::whereNull('user_id')->orWhereDoesntHave('user')->count();
        $missingVendors = \App\Models\User::where('role', 'vendeur')->whereDoesntHave('vendor')->count();
        
        if ($orphanVendors > 0 || $missingVendors > 0) {
            Log::warning('Vendor inconsistency detected', [
                'orphan_vendors' => $orphanVendors,
                'missing_vendors' => $missingVendors
            ]);
            
            // Nettoyer automatiquement avec la même logique que la commande
            $this->executeVendorSync();
        }
    }
    
    /**
     * Exécuter la synchronisation des vendors
     */
    private function executeVendorSync(): void
    {
        DB::transaction(function() {
            // Supprimer les vendors sans user
            $orphanCount = Vendor::whereNull('user_id')
                ->orWhereDoesntHave('user')
                ->forceDelete();
            
            if ($orphanCount > 0) {
                Log::info("Cleaned {$orphanCount} orphan vendors");
            }
            
            // Créer les vendors manquants
            $missingUsers = \App\Models\User::where('role', 'vendeur')
                ->whereDoesntHave('vendor')
                ->get();
            
            foreach($missingUsers as $user) {
                Vendor::create([
                    'user_id' => $user->id,
                    'shop_name' => $user->name . ' Boutique',
                    'description' => 'Boutique de ' . $user->name,
                    'status' => 'pending',
                ]);
                
                Log::info("Created vendor for user: {$user->email}");
            }
            
            if ($missingUsers->count() > 0) {
                Log::info("Created {$missingUsers->count()} missing vendors");
            }
        });
    }

    /**
     * Détails d’un vendeur (admin)
     */
    public function show(Vendor $vendor): View
    {
        $vendor->load(['user', 'products']);

        return view('admin.vendors.show', compact('vendor'));
    }

    /**
     * Approuver un vendeur
     */
    public function approve(Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'approved',
        ]);

        // Sécurité : activer aussi l'utilisateur
        $vendor->user->update([
            'is_active' => true,
        ]);

        if ($vendor->user) {
            SendVendorNotificationJob::dispatch(
                $vendor->user,
                new VendorApproved($vendor)
            );
        }

        return redirect()
            ->route('admin.vendors.index')
            ->with('success', 'Vendeur approuvé avec succès.');
    }

    /**
     * Suspendre un vendeur
     */
    public function suspend(Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'suspended',
        ]);

        // Sécurité : bloquer l'utilisateur
        $vendor->user->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.vendors.index')
            ->with('success', 'Vendeur suspendu.');
    }

    /**
     * Réactiver un vendeur suspendu
     */
    public function reactivate(Vendor $vendor): RedirectResponse
    {
        $vendor->update([
            'status' => 'approved',
        ]);

        $vendor->user->update([
            'is_active' => true,
        ]);

        if ($vendor->user) {
            SendVendorNotificationJob::dispatch(
                $vendor->user,
                new VendorApproved($vendor)
            );
        }

        return redirect()
            ->route('admin.vendors.index')
            ->with('success', 'Vendeur réactivé.');
    }
}
