@extends('client.layout')

@section('title', $vendor->shop_name . ' - Boutique')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl shadow p-6 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <img src="{{ $vendor->image_url }}"
                 alt="{{ $vendor->shop_name }}"
                 class="w-20 h-20 rounded-xl object-cover border border-gray-200"
                 onerror="this.src='{{ asset('images/default-vendor-logo.svg') }}'">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $vendor->shop_name }}</h1>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $vendor->description ?: 'Boutique partenaire sur GuinéeMall.' }}
                </p>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Produits de la boutique</h2>
        <span class="text-sm text-gray-500">{{ $products->total() }} produit(s)</span>
    </div>

    @if($products->isEmpty())
        <div class="bg-white rounded-xl shadow p-12 text-center">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucun produit disponible</h3>
            <p class="text-gray-600">Cette boutique n'a pas encore de produits actifs.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($products as $product)
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
                        <div class="flex gap-2">
                            <a href="{{ route('client.catalog.show', $product) }}"
                               class="flex-1 bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition text-sm font-semibold text-center">
                                Voir détails
                            </a>
                            @livewire('favorite-button', ['productId' => $product->id, 'compact' => true], key('vendor-fav-'.$product->id))
                            @livewire('add-to-cart-button', ['productId' => $product->id, 'compact' => true], key('vendor-cart-'.$product->id))
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
