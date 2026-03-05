<?php

namespace App\Livewire;

use App\Models\Cart;
use App\Models\Product;
use App\Services\Client\CartService;
use Illuminate\View\View;
use Livewire\Component;

class CartPage extends Component
{
    public $cartItems = [];
    public $total = 0;
    public $loading = false;

    protected $listeners = [
        'cartUpdated' => 'refreshCart',
        'cartItemAdded' => 'refreshCart',
        'cartItemRemoved' => 'refreshCart',
        'cartCleared' => 'refreshCart',
    ];

    public function mount(): void
    {
        $this->refreshCart();
    }

    public function refreshCart(): void
    {
        if (!auth()->check()) {
            $this->cartItems = [];
            $this->total = 0;
            return;
        }

        $this->cartItems = Cart::where('user_id', auth()->id())
            ->with(['product.category', 'product.vendor'])
            ->whereHas('product', function ($query) {
                $query->where('status', 'active')
                      ->where('stock', '>', 0);
            })
            ->get();

        $this->calculateTotal();
    }

    public function updateQuantity($cartId, $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($cartId);
            return;
        }

        $cartItem = Cart::find($cartId);
        if (!$cartItem || $cartItem->user_id !== auth()->id()) {
            return;
        }

        $maxQuantity = $cartItem->product->stock;
        $quantity = min($quantity, $maxQuantity);

        $cartItem->update(['quantity' => $quantity]);

        $this->dispatch('cartUpdated');
        $this->dispatch('showNotification', 
            message: 'Quantité mise à jour', 
            type: 'success'
        );

        $this->refreshCart();
    }

    public function removeItem($cartId): void
    {
        $cartItem = Cart::find($cartId);
        if (!$cartItem || $cartItem->user_id !== auth()->id()) {
            return;
        }

        $cartItem->delete();

        $this->dispatch('cartItemRemoved', cartId: $cartId);
        $this->dispatch('cartUpdated');
        $this->dispatch('showNotification', 
            message: 'Article retiré du panier', 
            type: 'success'
        );

        $this->refreshCart();
    }

    public function clearCart(): void
    {
        Cart::where('user_id', auth()->id())->delete();

        $this->dispatch('cartCleared');
        $this->dispatch('cartUpdated');
        $this->dispatch('showNotification', 
            message: 'Panier vidé', 
            type: 'success'
        );

        $this->refreshCart();
    }

    private function calculateTotal(): void
    {
        $this->total = $this->cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
    }

    public function getIsEmptyProperty(): bool
    {
        return $this->cartItems->isEmpty();
    }

    public function render(): View
    {
        return view('livewire.cart-page');
    }
}
