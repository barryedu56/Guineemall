<?php

namespace App\Services\Client;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Exception;

class CartService
{
    /**
     * Ajoute un produit au panier
     */
    public function addToCart(User $user, Product $product, int $quantity): Cart
    {
        // Vérifier la disponibilité du produit
        if ($product->status !== 'active') {
            throw new Exception('Ce produit n\'est pas disponible.');
        }

        if ($product->stock <= 0) {
            throw new Exception('Ce produit est en rupture de stock.');
        }

        // Vérifier si le produit est déjà dans le panier
        $cartItem = Cart::forUser($user->id)
                       ->where('product_id', $product->id)
                       ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            
            if ($newQuantity > $product->stock) {
                throw new Exception("Quantité totale non disponible. Stock disponible: {$product->stock}");
            }
            
            $cartItem->update(['quantity' => $newQuantity]);
            return $cartItem;
        }

        // Vérifier la quantité demandée
        if ($quantity > $product->stock) {
            throw new Exception("Quantité demandée non disponible. Stock disponible: {$product->stock}");
        }

        return Cart::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Met à jour la quantité d'un article dans le panier
     */
    public function updateCartItem(User $user, int $cartItemId, int $quantity): Cart
    {
        $cartItem = Cart::findOrFail($cartItemId);

        // Vérifier que l'article appartient à l'utilisateur
        if ($cartItem->user_id !== $user->id) {
            throw new Exception('Non autorisé.');
        }

        $product = $cartItem->product;

        if ($quantity > $product->stock) {
            throw new Exception("Quantité non disponible. Stock disponible: {$product->stock}");
        }

        if ($quantity <= 0) {
            $cartItem->delete();
            throw new Exception('Article supprimé du panier.');
        }

        $cartItem->update(['quantity' => $quantity]);
        return $cartItem;
    }

    /**
     * Calcule le total du panier
     */
    public function getCartTotal(User $user): float
    {
        return Cart::forUser($user->id)
                  ->withAvailableProducts()
                  ->with(['product'])
                  ->get()
                  ->sum(function ($item) {
                      return $item->quantity * $item->product->price;
                  });
    }

    /**
     * Nettoie les articles du panier avec des produits non disponibles
     */
    public function cleanCart(User $user): int
    {
        $unavailableItems = Cart::forUser($user->id)
                               ->whereHas('product', function ($query) {
                                   $query->where('status', '!=', 'active')
                                         ->orWhere('stock', '<=', 0);
                               })
                               ->get();

        $count = $unavailableItems->count();
        $unavailableItems->each->delete();

        return $count;
    }
}
