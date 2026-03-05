<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    /**
     * Afficher la liste des favoris du client
     */
    public function index()
    {
        $favorites = auth()->user()
            ->favorites()
            ->with(['category', 'vendor'])
            ->where('status', 'active')
            ->orderBy('name')
            ->paginate(12);

        return view('client.favorites.index', compact('favorites'));
    }

    /**
     * Ajouter ou retirer un produit des favoris
     */
    public function toggle(Product $product): JsonResponse
    {
        $user = auth()->user();
        
        // Vérifier si le produit est dans les favoris
        $isFavorite = $user->favorites()
            ->where('product_id', $product->id)
            ->exists();

        if ($isFavorite) {
            // Retirer des favoris
            $user->favorites()->detach($product->id);
            $message = __('messages.favorites.removed');
            $isFavorite = false;
        } else {
            // Ajouter aux favoris
            $user->favorites()->attach($product->id);
            $message = __('messages.favorites.added');
            $isFavorite = true;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'is_favorite' => $isFavorite,
            'product_id' => $product->id
        ]);
    }
}
