{{-- resources/views/client/catalog/index.blade.php --}}
@extends('client.layout')

@section('title', 'Catalogue - GuinéeMall')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex gap-8">
        {{-- Sidebar Filters --}}
        <aside class="hidden lg:block w-64 flex-shrink-0">
            <div class="bg-white rounded-xl shadow p-6 sticky top-24">
                <h3 class="font-bold text-lg mb-4">Filtres</h3>
                
                <form method="GET" action="{{ route('client.catalog.index') }}">
                    {{-- Categories --}}
                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Catégories</h4>
                        @foreach($categories as $category)
                            <label class="flex items-center mb-2">
                                <input type="radio" 
                                       name="category" 
                                       value="{{ $category->id }}"
                                       {{ (request('category_id') == $category->id || optional(request()->route('category'))->id == $category->id) ? 'checked' : '' }}
                                       class="text-yellow-600 focus:ring-yellow-500">
                                <span class="ml-2 text-sm">{{ $category->name }} ({{ $category->products_count }})</span>
                            </label>
                        @endforeach
                    </div>

                    {{-- Price Range --}}
                    <div class="mb-6">
                        <h4 class="font-semibold mb-3">Prix</h4>
                        <div class="space-y-3">
                            <input type="number" 
                                   name="min_price" 
                                   placeholder="Min" 
                                   value="{{ request('min_price') }}"
                                   class="w-full px-3 py-2 border rounded-lg">
                            <input type="number" 
                                   name="max_price" 
                                   placeholder="Max"
                                   value="{{ request('max_price') }}"
                                   class="w-full px-3 py-2 border rounded-lg">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-yellow-600 text-white py-2 rounded-lg hover:bg-yellow-700 transition">
                        Appliquer les filtres
                    </button>
                    
                    @if(request()->anyFilled(['category', 'min_price', 'max_price', 'search']))
                        <a href="{{ route('client.catalog.index') }}" class="block text-center text-sm text-gray-600 mt-3 hover:text-gray-800">
                            Réinitialiser
                        </a>
                    @endif
                </form>
            </div>
        </aside>

        {{-- Products Grid --}}
        <div class="flex-1">
            <div class="mb-6">
                <h1 class="text-3xl font-bold mb-2">Catalogue Produits</h1>
                <p class="text-gray-600">{{ $products->total() }} produits disponibles</p>
            </div>

            @if($products->isEmpty())
                <div class="bg-white rounded-xl shadow p-12 text-center">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucun produit trouvé</h3>
                    <p class="text-gray-600">Essayez de modifier vos filtres</p>
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
                                @if($product->stock <= 0)
                                    <span class="absolute top-2 right-2 bg-gray-500 text-white text-xs px-2 py-1 rounded">
                                        Rupture
                                    </span>
                                @elseif($product->stock < 10)
                                    <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                        Stock limité
                                    </span>
                                @endif
                            </a>
                            <div class="p-4">
                                <p class="text-xs text-gray-500 mb-1">{{ $product->category->name }}</p>
                                <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2">
                                    <a href="{{ route('client.catalog.show', $product) }}" class="hover:text-yellow-600">
                                        {{ $product->name }}
                                    </a>
                                </h3>
                                <p class="text-yellow-600 font-bold text-lg mb-2">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                                <p class="text-xs text-gray-500 mb-3">Vendeur: {{ $product->vendor->shop_name }}</p>
                                
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
                                    
                                    @livewire('favorite-button', ['product' => $product, 'compact' => true], key('fav-'.$product->id))
                                    @livewire('add-to-cart-button', ['product' => $product, 'compact' => true], key('cart-btn-'.$product->id))
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection