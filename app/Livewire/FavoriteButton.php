<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\View\View;
use Livewire\Component;

class FavoriteButton extends Component
{
    public Product $product;
    public bool $isFavorite = false;
    public ?bool $compact = false;

    public function mount(Product $product, ?bool $compact = false): void
    {
        $this->product = $product;
        $this->compact = $compact;
        $this->checkFavorite();
    }

    public function toggleFavorite(): void
    {
        if (!auth()->check()) {
            $this->dispatch('showLoginModal');
            return;
        }

        $user = auth()->user();
        
        if ($this->isFavorite) {
            // Retirer des favoris
            $user->favorites()->detach($this->product->id);
            $this->isFavorite = false;
            $this->dispatch('favoriteRemoved', productId: $this->product->id);
        } else {
            // Ajouter aux favoris
            $user->favorites()->attach($this->product->id);
            $this->isFavorite = true;
            $this->dispatch('favoriteAdded', productId: $this->product->id);
        }
    }

    private function checkFavorite(): void
    {
        if (!auth()->check()) {
            $this->isFavorite = false;
            return;
        }

        $this->isFavorite = auth()->user()
            ->favorites()
            ->where('product_id', $this->product->id)
            ->exists();
    }

    public function render(): View
    {
        return view('livewire.favorite-button');
    }
}
