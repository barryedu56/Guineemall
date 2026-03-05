<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\Client\CartService;
use Illuminate\View\View;
use Livewire\Component;

class AddToCartButton extends Component
{
    public Product $product;
    public bool $compact = false;
    public int $quantity = 1;
    public bool $loading = false;

    protected $listeners = [
        'cartUpdated' => '$refresh',
        'cartItemAdded' => '$refresh',
        'cartItemRemoved' => '$refresh',
    ];

    public function mount(Product $product, ?bool $compact = false): void
    {
        $this->product = $product;
        $this->compact = $compact;
        $this->quantity = 1;
    }

    public function addToCart(): void
    {
        if (!auth()->check()) {
            $this->dispatch('showLoginModal');
            return;
        }

        $this->loading = true;

        try {
            $cartService = app(CartService::class);
            $cartService->addToCart(auth()->user(), $this->product, $this->quantity);

            $this->dispatch('cartItemAdded', productId: $this->product->id);
            $this->dispatch('cartUpdated');
            $this->dispatch('showNotification', 
                message: 'Produit ajouté au panier', 
                type: 'success'
            );

        } catch (\Exception $e) {
            $this->dispatch('showNotification', 
                message: $e->getMessage(), 
                type: 'error'
            );
        } finally {
            $this->loading = false;
        }
    }

    public function incrementQuantity(): void
    {
        if ($this->quantity < $this->product->stock) {
            $this->quantity++;
        }
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function getIsInCartProperty(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()
            ->cartItems()
            ->where('product_id', $this->product->id)
            ->exists();
    }

    public function render(): View
    {
        return view('livewire.add-to-cart-button');
    }
}
