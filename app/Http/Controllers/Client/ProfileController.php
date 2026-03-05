<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Affiche le profil du client
     */
    public function index(): View
    {
        $user = auth()->user();
        $ordersCount = $user->orders()->count();
        $cartItemsCount = $user->cartItems()->count();
        $totalSpent = $user->orders()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        $loyaltyPoints = (int) floor($totalSpent / 10000);
        $recentOrders = $user->orders()->latest()->take(3)->get();

        return view('client.profile.index', compact(
            'user',
            'ordersCount',
            'cartItemsCount',
            'totalSpent',
            'loyaltyPoints',
            'recentOrders'
        ));
    }

    /**
     * Affiche le formulaire d'édition du profil
     */
    public function edit(): View
    {
        $user = auth()->user();

        return view('client.profile.edit', compact('user'));
    }

    /**
     * Met à jour le profil du client
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validated();

        // Mettre à jour le mot de passe si fourni
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('client.profile.index')
                       ->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Met à jour le mot de passe du client
     */
    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('client.profile.index')
                       ->with('success', 'Mot de passe mis à jour avec succès.');
    }

    /**
     * Affiche le tableau de bord client
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        // Statistiques
        $stats = [
            'total_orders' => $user->orders()->count(),
            'pending_orders' => $user->orders()->where('status', 'pending')->count(),
            'delivered_orders' => $user->orders()->where('status', 'delivered')->count(),
            'cart_items' => $user->cartItems()->count(),
        ];

        // Commandes récentes
        $recentOrders = $user->orders()
                             ->with(['orderItems.product'])
                             ->orderBy('created_at', 'desc')
                             ->take(5)
                             ->get();

        // Articles récents dans le panier
        $cartItems = $user->cartItems()
                         ->withAvailableProducts()
                         ->with(['product.category', 'product.vendor'])
                         ->take(3)
                         ->get();

        // Récupérer les catégories dynamiques
        $categories = \App\Models\Category::query()
            ->withCount(['products' => function ($query) {
                $query->where('status', 'active');
            }])
            ->where('status', 'active')
            ->orderBy('name')
            ->take(6)
            ->get();

        // Produits en vedette dynamiques
        $featuredProducts = \App\Models\Product::where('status', 'active')
            ->with(['category', 'vendor'])
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get();

        return view('client.dashboard', compact(
            'stats', 
            'recentOrders', 
            'cartItems',
            'categories',
            'featuredProducts'
        ));
    }
}
