<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold mb-8">Votre Panier</h1>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-yellow-700 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-red-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($cartItems->isEmpty())
        <div class="bg-white rounded-xl shadow p-12 text-center">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Votre panier est vide</h3>
            <p class="text-gray-600 mb-6">Découvrez nos produits et ajoutez-les à votre panier</p>
            <a href="{{ route('client.catalog.index') }}"
               class="inline-block bg-yellow-600 text-white px-8 py-3 rounded-lg hover:bg-yellow-700 transition">
                Voir les produits
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl shadow overflow-hidden">
            @foreach($cartItems as $item)
                <div class="p-6 border-b last:border-b-0 flex flex-col sm:flex-row sm:items-center gap-6">
                    <img src="{{ $item->product->image_url }}"
                         alt="{{ $item->product->name }}"
                         class="w-24 h-24 object-cover rounded-lg"
                         loading="lazy">

                    <div class="flex-1">
                        <h3 class="font-semibold text-lg mb-1">{{ $item->product->name }}</h3>
                        <p class="text-sm text-gray-600 mb-2">{{ number_format($item->product->price, 0, ',', ' ') }} GNF</p>
                        <p class="text-xs text-gray-500">Vendeur: {{ $item->product->vendor->shop_name }}</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                @disabled($item->quantity <= 1)
                                class="w-8 h-8 bg-gray-100 rounded hover:bg-gray-200 transition disabled:opacity-50">
                            -
                        </button>
                        <input type="number" 
                               wire:model.debounce.300ms="cartItems.{{ $loop->index }}.quantity"
                               min="1" 
                               max="{{ $item->product->stock }}"
                               class="w-12 text-center font-semibold border rounded">
                        <button wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                @disabled($item->quantity >= $item->product->stock)
                                class="w-8 h-8 bg-gray-100 rounded hover:bg-gray-200 transition disabled:opacity-50">
                            +
                        </button>
                    </div>

                    <div class="text-right w-32">
                        <p class="text-lg font-bold text-yellow-600">
                            {{ number_format($item->quantity * $item->product->price, 0, ',', ' ') }} GNF
                        </p>
                    </div>

                    <button wire:click="removeItem({{ $item->id }})"
                            class="text-red-500 hover:text-red-700 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            @endforeach

            <div class="bg-gray-50 p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-gray-600">Sous-total</span>
                    <span class="font-semibold">{{ number_format($total, 0, ',', ' ') }} GNF</span>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <span class="text-sm text-gray-600">Frais de livraison</span>
                    <span class="font-semibold">0 GNF</span>
                </div>
                <div class="flex items-center justify-between mb-6">
                    <span class="text-xl font-semibold">Total de la commande</span>
                    <span class="text-3xl font-bold text-yellow-600">{{ number_format($total, 0, ',', ' ') }} GNF</span>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button wire:click="clearCart"
                            class="w-full sm:w-auto border border-gray-300 text-gray-700 px-4 py-3 rounded-xl font-semibold hover:bg-gray-100 transition">
                        Vider le panier
                    </button>
                    <a href="{{ route('client.orders.checkout') }}"
                       class="block w-full bg-yellow-600 text-white text-center py-4 rounded-xl font-bold text-lg hover:bg-yellow-700 transition">
                        Passer la commande
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
