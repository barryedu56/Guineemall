<form wire:submit="addToCart"
      method="POST"
      action="{{ route('client.cart.add') }}"
      class="flex flex-col gap-3">
    @csrf

    @if($product && $product->stock > 0 && $product->status === 'active')
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        @if($compact)
            <input type="hidden" name="quantity" value="1">
            <button type="submit"
                    class="bg-yellow-100 text-yellow-600 px-4 py-2 rounded-lg hover:bg-yellow-200 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </button>
        @else
            <div class="flex items-center gap-3">
                <button wire:click="decrement"
                        @disabled($quantity <= 1)
                        class="w-12 h-12 bg-gray-100 rounded-lg hover:bg-gray-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        type="button">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
                <input type="number"
                       name="quantity"
                       wire:model="quantity"
                       min="1"
                       max="{{ $product->stock }}"
                       class="w-20 text-center border-2 border-gray-200 rounded-lg py-3 text-lg font-semibold focus:border-yellow-500 focus:outline-none">
                <button wire:click="increment"
                        @disabled($quantity >= $product->stock)
                        class="w-12 h-12 bg-gray-100 rounded-lg hover:bg-gray-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                        type="button">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                <span class="text-sm text-gray-500 ml-2">Disponible: {{ $product->stock }}</span>
            </div>

            <button type="submit"
                    class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 text-white py-4 px-6 rounded-xl font-bold text-lg hover:from-yellow-600 hover:to-orange-600 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <span class="flex items-center justify-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Ajouter au panier
                </span>
            </button>
        @endif
    @else
        <button class="w-full bg-gray-200 text-gray-500 py-3 px-6 rounded-xl font-bold text-lg cursor-not-allowed"
                type="button" disabled>
            Produit indisponible
        </button>
    @endif

    @if(isset($message) && $message)
        <p class="text-sm text-yellow-600 font-medium">{{ $message }}</p>
    @endif
    @if($errors->any())
        <p class="text-sm text-red-600 font-medium">{{ $errors->first() }}</p>
    @endif
</form>
