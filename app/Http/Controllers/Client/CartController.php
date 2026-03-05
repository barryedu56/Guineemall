<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Http\Requests\Client\AddToCartRequest;
use App\Http\Requests\Client\UpdateCartRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Affiche le panier du client
     */
    public function index(): View
    {
        $cartItems = Cart::forUser(auth()->id())
                        ->withAvailableProducts()
                        ->with(['product.category', 'product.vendor'])
                        ->get();

        $total = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return view('client.cart.index', compact('cartItems', 'total'));
    }

    /**
     * Ajoute un produit au panier
     */
    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);

        // Vérifier que le produit est disponible
        if ($product->status !== 'active' || $product->stock <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.cart.product_unavailable'),
                ], 422);
            }

            return back()->with('error', __('messages.cart.product_unavailable'));
        }

        // Vérifier le stock
        if ($request->quantity > $product->stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.cart.quantity_exceeds_stock'),
                ], 422);
            }

            return back()->with('error', __('messages.cart.quantity_exceeds_stock'));
        }

        // Ajouter ou mettre à jour l'article dans le panier
        $cartItem = Cart::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ],
            [
                'quantity' => 0,
            ]
        );

        $cartItem->increment('quantity', $request->quantity);
        $cartItem->refresh();

        // Mettre à jour le panier si la quantité dépasse le stock
        if ($cartItem->quantity > $product->stock) {
            $cartItem->update(['quantity' => $product->stock]);
        }

        $message = __('messages.cart.added');
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'cart_count' => Cart::forUser(auth()->id())->sum('quantity')
            ]);
        }

        return redirect()->route('client.cart.index')->with('success', $message);
    }

    /**
     * Affiche la page de checkout
     */
    public function checkout(): View|RedirectResponse
    {
        $cartItems = Cart::forUser(auth()->id())
                        ->withAvailableProducts()
                        ->with(['product.category', 'product.vendor'])
                        ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('client.cart.index')
                           ->with('error', __('messages.cart.empty'));
        }

        $total = $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        return view('client.cart.checkout', compact('cartItems', 'total'));
    }

    /**
     * Met à jour la quantité d'un article dans le panier
     */
    public function update(UpdateCartRequest $request, Cart $cart): RedirectResponse|JsonResponse
    {
        // Vérifier que le panier appartient à l'utilisateur connecté
        if ($cart->user_id !== auth()->id()) {
            abort(403);
        }

        $product = $cart->product;

        // Vérifier que la nouvelle quantité est disponible
        if ($request->quantity > $product->stock) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                'message' => __('messages.cart.quantity_unavailable', ['stock' => $product->stock]),
                ], 422);
            }

        return back()->with('error', __('messages.cart.quantity_unavailable', ['stock' => $product->stock]));
        }

        if ($request->quantity > 0) {
            $cart->update(['quantity' => $request->quantity]);
        } else {
            $cart->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.cart.updated'),
            ]);
        }

        return back()->with('success', __('messages.cart.updated'));
    }

    /**
     * Supprime un article du panier
     */
    public function remove(Cart $cart): RedirectResponse|JsonResponse
    {
        // Vérifier que le panier appartient à l'utilisateur connecté
        if ($cart->user_id !== auth()->id()) {
            abort(403);
        }

        $cart->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.cart.removed'),
            ]);
        }

        return back()->with('success', __('messages.cart.removed'));
    }

    /**
     * Vide le panier
     */
    public function clear(): RedirectResponse|JsonResponse
    {
        Cart::forUser(auth()->id())->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.cart.cleared'),
            ]);
        }

        return back()->with('success', __('messages.cart.cleared'));
    }
}
