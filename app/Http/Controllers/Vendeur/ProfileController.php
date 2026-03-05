<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Vendor;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Resources\VendorResource;

class ProfileController extends Controller
{
    /**
     * Constructeur avec nettoyage automatique
     */
    public function __construct()
    {
        $this->ensureVendorConsistency();
    }
    
    /**
     * Garantir la cohérence des vendors
     */
    private function ensureVendorConsistency(): void
    {
        // Nettoyer les vendors orphelins
        Vendor::whereNull('user_id')->orWhereDoesntHave('user')->forceDelete();
        
        // Créer les vendors manquants pour les users vendeur
        User::where('role', 'vendeur')->whereDoesntHave('vendor')->get()
            ->each(function($user) {
                Vendor::create([
                    'user_id' => $user->id,
                    'shop_name' => $user->name . ' Boutique',
                    'description' => 'Boutique de ' . $user->name,
                    'status' => 'pending',
                ]);
            });
    }

    /**
     * Afficher le profil du vendeur
     */
    public function index(): View|RedirectResponse
    {
        $vendor = auth()->user()->vendor;
        
        if (!$vendor) {
            return redirect()->route('vendeur.profile.create')
                ->with('info', __('messages.vendor.profile_required'));
        }
        
        return view('vendeur.profile.index', compact('vendor'));
    }
    
    /**
     * Afficher la boutique publique du vendeur
     */
    public function show(): View|RedirectResponse
    {
        $vendor = auth()->user()->vendor;
        
        if (!$vendor) {
            return redirect()->route('vendeur.profile.create')
                ->with('info', __('messages.vendor.profile_required'));
        }
        
        // Récupérer les produits du vendeur
        $products = $vendor->products()->where('status', 'active')->get();
        
        return view('vendeur.shop.show', compact('vendor', 'products'));
    }
    
    /**
     * Afficher le formulaire de création de boutique
     */
    public function create(): View|RedirectResponse
    {
        $vendor = auth()->user()->vendor;
        
        if ($vendor) {
            return redirect()->route('vendeur.profile.index');
        }
        
        return view('vendeur.profile.create');
    }
    
    /**
     * Créer une nouvelle boutique
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'shop_name' => 'required|string|max:255|unique:vendors,shop_name',
            'description' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'shop_name.unique' => 'Ce nom de boutique est déjà utilisé.',
        ]);
        
        try {
            $vendorData = [
                'user_id' => auth()->id(),
                'shop_name' => $request->shop_name,
                'description' => $request->description,
                'status' => 'pending', // En attente de validation admin
            ];
            
            // Créer le vendeur d'abord
            $vendor = Vendor::create($vendorData);
            
            // Stocker le logo si fourni en utilisant le trait
            if ($request->hasFile('logo')) {
                $vendor->storeImage($request->file('logo'));
            }
            
            // Mettre à jour les informations de contact de l'utilisateur
            auth()->user()->update([
                'phone' => $request->phone,
                'delivery_address' => $request->address,
            ]);
            
            return redirect()
                ->route('vendeur.profile.index')
                ->with('success', __('messages.vendor.profile_created'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de la boutique: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher le formulaire d'édition du profil
     */
    public function edit(): View|RedirectResponse
    {
        $vendor = auth()->user()->vendor;
        
        if (!$vendor) {
            return redirect()->route('vendeur.profile.create');
        }

        $this->authorize('view', $vendor);
        
        return view('vendeur.profile.edit', compact('vendor'));
    }
    
    /**
     * Mettre à jour le profil du vendeur
     */
    public function update(Request $request): RedirectResponse
    {
        $vendor = auth()->user()->vendor;
        
        if (!$vendor) {
            return redirect()->route('vendeur.profile.create');
        }

        $this->authorize('update', $vendor);
        
        $request->validate([
            'shop_name' => 'required|string|max:255|unique:vendors,shop_name,' . $vendor->id,
            'description' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'shop_name.unique' => 'Ce nom de boutique est déjà utilisé.',
        ]);
        
        try {
            $vendorData = [
                'shop_name' => $request->shop_name,
                'description' => $request->description,
            ];
            
            // Mettre à jour les données du vendeur
            $vendor->update($vendorData);
            
            // Stocker le nouveau logo si fourni en utilisant le trait
            if ($request->hasFile('logo')) {
                $vendor->storeImage($request->file('logo'));
            }
            
            // Mettre à jour les informations de contact de l'utilisateur
            auth()->user()->update([
                'phone' => $request->phone,
                'delivery_address' => $request->address,
            ]);
            
            return redirect()
                ->route('vendeur.profile.index')
                ->with('success', __('messages.vendor.profile_updated'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du profil: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher le formulaire de changement de mot de passe
     */
    public function editPassword(): View
    {
        return view('vendeur.profile.password');
    }
    
    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);
        
        try {
            $user = auth()->user();
            $user->update([
                'password' => Hash::make($request->password),
            ]);
            
            return redirect()
                ->route('vendeur.profile.index')
                ->with('success', __('messages.vendor.password_updated'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du mot de passe: ' . $e->getMessage());
        }
    }
    
    /**
     * Supprimer le logo
     */
    public function deleteLogo(): RedirectResponse
    {
        $vendor = auth()->user()->vendor;
        
        if (!$vendor || !$vendor->logo) {
            return redirect()->back();
        }

        $this->authorize('update', $vendor);
        
        try {
            // Supprimer le logo en utilisant le trait
            $vendor->deleteImage();
            
            return redirect()
                ->back()
                ->with('success', __('messages.vendor.logo_deleted'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du logo: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtenir les informations du vendeur (API)
     */
    public function info()
    {
        $vendor = auth()->user()->vendor;
        
        if (!$vendor) {
            return response()->json([
                'error' => __('messages.vendor.no_shop'),
            ], 404);
        }
        
        return response()->json([
            'vendor' => new VendorResource($vendor),
            'user' => new UserResource(auth()->user()),
        ]);
    }
}
