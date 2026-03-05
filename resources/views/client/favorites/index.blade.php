@extends('client.layout')

@section('title', 'Mes Favoris - GuinéeMall')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold mb-2">Mes Favoris</h1>
        <p class="text-gray-600">{{ $favorites->total() }} produit(s) enregistrés</p>
    </div>

    @if($favorites->isEmpty())
        <div class="bg-white rounded-xl shadow p-12 text-center">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucun favori</h3>
            <p class="text-gray-600 mb-6">Ajoutez des produits à vos favoris pour les retrouver ici</p>
            <a href="{{ route('client.catalog.index') }}"
               class="inline-block bg-yellow-600 text-white px-8 py-3 rounded-lg hover:bg-yellow-700 transition">
                Découvrir les produits
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($favorites as $product)
                <div class="bg-white rounded-xl shadow hover:shadow-xl transition overflow-hidden">
                    <a href="{{ route('client.catalog.show', $product) }}" class="block aspect-square bg-gray-200 relative">
                        <img src="{{ $product->image_url }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
                    </a>
                    <div class="p-4">
                        <p class="text-xs text-gray-500 mb-1">{{ $product->category->name ?? '—' }}</p>
                        <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                            <a href="{{ route('client.catalog.show', $product) }}" class="hover:text-yellow-600">
                                {{ $product->name }}
                            </a>
                        </h3>
                        <p class="text-yellow-600 font-bold text-lg mb-2">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                        <p class="text-xs text-gray-500 mb-3">Vendeur: {{ $product->vendor->shop_name ?? '—' }}</p>

                        <div class="flex gap-2">
                            <a href="{{ route('client.catalog.show', $product) }}"
                               class="flex-1 bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition text-sm font-semibold text-center">
                                Voir détails
                            </a>
                            {{-- Bouton de discussion rapide --}}
                            @if(auth()->check() && auth()->user()->role === 'client' && $product->vendor)
                                <a href="{{ route('chat.product', $product) }}"
                                   class="bg-orange-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 transition text-sm font-semibold"
                                   title="Discuter avec le vendeur">
                                    <i class="fas fa-comments"></i>
                                </a>
                            @endif
                            @livewire('favorite-button', ['product' => $product, 'compact' => true], key('fav-list-'.$product->id))
                            @livewire('add-to-cart-button', ['product' => $product, 'compact' => true], key('fav-cart-'.$product->id))
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $favorites->links() }}
        </div>
    @endif
</div>
@endsection
