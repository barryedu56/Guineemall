<?php

namespace App\Livewire;

use App\Models\Cart;
use Illuminate\View\View;
use Livewire\Component;

class CartCounter extends Component
{
    public int $count = 0;

    protected $listeners = [
        'cartUpdated' => 'updateCount',
        'cartItemAdded' => 'updateCount',
        'cartItemRemoved' => 'updateCount',
        'cartCleared' => 'updateCount',
    ];

    public function mount(): void
    {
        $this->updateCount();
    }

    public function updateCount(): void
    {
        $this->count = $this->getCartCount();
    }

    private function getCartCount(): int
    {
        if (!auth()->check()) {
            return 0;
        }

        return Cart::where('user_id', auth()->id())
            ->whereHas('product', function ($query) {
                $query->where('status', 'active')
                      ->where('stock', '>', 0);
            })
            ->sum('quantity');
    }

    public function render(): View
    {
        return view('livewire.cart-counter');
    }
}
