{{-- resources/views/client/catalog/show.blade.php --}}
@extends('client.layout')

@section('title', $product->name . ' - GuinéeMall')

@section('content')
@php
    $imageUrl = $product->image_url;
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ currentImage: '{{ $imageUrl }}' }">
    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm">
        <ol class="flex items-center space-x-2 text-gray-500">
            <li><a href="{{ route('dashboard') }}" class="hover:text-yellow-600 transition-colors">Accueil</a></li>
            <li class="text-gray-400">/</li>
            <li><a href="{{ route('client.catalog.index') }}" class="hover:text-yellow-600 transition-colors">Catalogue</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-900 font-medium">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="lg:grid lg:grid-cols-2 lg:gap-0">
            {{-- SECTION GAUCHE - IMAGES PRODUIT --}}
            <div class="p-8 lg:p-12 bg-gray-50">
                {{-- Image principale avec zoom --}}
                <div class="relative aspect-square bg-white rounded-xl shadow-lg overflow-hidden mb-6 group cursor-zoom-in" 
                     x-data="{ zoomed: false }"
                     @click="zoomed = !zoomed"
                     :class="zoomed ? 'fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center p-8' : ''">
                    <img src="{{ $imageUrl }}" 
                         :src="currentImage"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover transition-transform duration-300"
                         loading="lazy"
                         :class="zoomed ? 'max-w-4xl max-h-4xl' : 'group-hover:scale-105'"
                         onerror="this.src='{{ asset('images/default-product.svg') }}'">
                    
                    @if($product->image)
                        <div class="absolute top-4 right-4 bg-white rounded-full p-2 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                            </svg>
                        </div>
                    @endif
                </div>
                
                {{-- Galerie de miniatures --}}
                @if($product->image)
                    <div class="grid grid-cols-4 gap-3">
                        {{-- Miniature principale --}}
                        <button @click="currentImage = '{{ $imageUrl }}'"
                                class="aspect-square bg-white rounded-lg overflow-hidden border-2 transition-all hover:shadow-md"
                                :class="currentImage === '{{ $imageUrl }}' ? 'border-yellow-500 shadow-md scale-105' : 'border-gray-200'">
                            <img src="{{ $imageUrl }}" 
                                 alt="{{ $product->name }} thumbnail"
                                 class="w-full h-full object-cover transition-transform hover:scale-110"
                                 loading="lazy"
                                 onerror="this.src='{{ asset('images/default-product.svg') }}'">
                        </button>
                        
                        {{-- Miniatures additionnelles (simulées) --}}
                        @for($i = 1; $i <= 3; $i++)
                            <button @click="currentImage = '{{ $imageUrl }}'"
                                    class="aspect-square bg-white rounded-lg overflow-hidden border-2 transition-all hover:shadow-md"
                                    :class="currentImage === '{{ $imageUrl }}' ? 'border-yellow-500 shadow-md scale-105' : 'border-gray-200'">
                                <img src="{{ $imageUrl }}" 
                                     alt="{{ $product->name }} vue {{ $i + 1 }}"
                                     class="w-full h-full object-cover transition-transform hover:scale-110 opacity-90 hover:opacity-100"
                                     loading="lazy"
                                     onerror="this.src='{{ asset('images/default-product.svg') }}'">
                            </button>
                        @endfor
                    </div>
                
                    {{-- Actions image --}}
                    <div class="flex gap-2 mt-4">
                        @livewire('favorite-button', ['product' => $product, 'compact' => false], key('fav-show-'.$product->id))
                        <button type="button"
                                id="share-product-button"
                                data-share-url="{{ request()->fullUrl() }}"
                                data-share-title="{{ $product->name }}"
                                class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 01-7.432 0m9.032-4.026A9.001 9.001 0 0112 3c-4.474 0-8.268 3.12-9.032 7.326m0 0A9.001 9.001 0 0012 21c4.474 0 8.268-3.12 9.032-7.326"/>
                            </svg>
                            Partager
                        </button>
                    </div>
                @endif
            </div>
            
            {{-- SECTION DROITE - INFORMATIONS PRODUIT --}}
            <div class="p-8 lg:p-12">
                {{-- En-tête produit --}}
                <div class="mb-6">
                    @if($product->category)
                        <div class="flex items-center gap-2 mb-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ $product->category->name }}
                            </span>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= 4 ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                                <span class="text-sm text-gray-600 ml-1">(4.5)</span>
                            </div>
                        </div>
                    @endif
                    
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4 leading-tight">
                        {{ $product->name }}
                    </h1>
                    
                    <div class="flex items-baseline gap-4 mb-4">
                        <p class="text-4xl lg:text-5xl font-bold text-yellow-600">
                            {{ number_format($product->price, 0, ',', ' ') }} GNF
                        </p>
                        @if($product->stock > 0)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                En stock ({{ $product->stock }})
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                Rupture de stock
                            </span>
                        @endif
                    </div>
                </div>
                
                {{-- Description --}}
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Description</h3>
                    <div class="prose prose-gray max-w-none">
                        <p class="text-gray-700 leading-relaxed">
                            {{ $product->description ?? 'Découvrez ce produit exceptionnel de qualité supérieure, fabriqué avec soin pour répondre à vos attentes les plus élevées.' }}
                        </p>
                    </div>
                </div>
                
                {{-- Ajout au panier --}}
                {{-- Actions principales --}}
                <div class="mb-6 space-y-3">
                    @if($product->stock > 0)
                        @livewire('add-to-cart-button', ['product' => $product, 'compact' => false], key('cart-btn-show-'.$product->id))
                    @else
                        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">
                            Ce produit est momentanément en rupture de stock. Revenez bientôt.
                        </div>
                    @endif
                    
                    {{-- Bouton de discussion avec le vendeur --}}
                    @if(auth()->check() && auth()->user()->role === 'client' && $product->vendor)
                        <a href="{{ route('chat.product', $product) }}" 
                           class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-6 py-3 rounded-xl font-semibold hover:from-yellow-600 hover:to-orange-600 transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i class="fas fa-comments mr-2"></i>
                            Discuter avec le vendeur
                        </a>
                    @elseif(!auth()->check())
                        <a href="{{ route('login') }}" 
                           class="w-full bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-comments mr-2"></i>
                            Connectez-vous pour discuter avec le vendeur
                        </a>
                    @endif
                </div>
                
                {{-- Informations vendeur --}}
                <div class="border-t pt-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Informations sur le vendeur</h3>
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-100">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                @if($product->vendor)
                                    <img src="{{ $product->vendor->image_url }}"
                                         alt="{{ $product->vendor->shop_name }}"
                                         class="w-16 h-16 rounded-xl object-cover border-2 border-white shadow-md"
                                         onerror="this.src='{{ asset('images/default-vendor-logo.svg') }}'">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-md">
                                        V
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-600 mb-1 font-medium">Vendu par</p>
                                <p class="text-lg font-bold text-gray-900 mb-2">
                                    {{ $product->vendor->shop_name ?? 'Boutique Partenaire' }}
                                </p>
                                
                                @if($product->vendor)
                                    <div class="flex items-center gap-4 text-sm">
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            Expédition sous 2-3 jours
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 100 4h2a2 2 0 100-4h2a1 1 0 100-2 2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2H6z" clip-rule="evenodd"/>
                                            </svg>
                                            Retour sous 14 jours
                                        </div>
                                    </div>
                                    
                                    <a href="{{ route('client.catalog.vendor', $product->vendor) }}"
                                       class="inline-flex items-center mt-3 text-yellow-600 hover:text-yellow-700 font-semibold text-sm transition-colors">
                                        Visiter la boutique
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Services et garanties --}}
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <svg class="w-8 h-8 mx-auto mb-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <p class="text-xs font-medium text-gray-900">Paiement sécurisé</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <svg class="w-8 h-8 mx-auto mb-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-xs font-medium text-gray-900">Livraison rapide</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <svg class="w-8 h-8 mx-auto mb-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <p class="text-xs font-medium text-gray-900">Retour facile</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if($similarProducts->isNotEmpty())
        <section class="mt-16">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl lg:text-3xl font-bold text-gray-900">Produits Similaires</h2>
                <a href="{{ route('client.catalog.index') }}" class="text-yellow-600 hover:text-yellow-700 font-semibold text-sm transition-colors">
                    Voir tout
                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($similarProducts as $related)
                    <div class="bg-white rounded-xl shadow hover:shadow-xl transition-all duration-300 overflow-hidden group cursor-pointer">
                        <a href="{{ route('client.catalog.show', $related) }}" class="block">
                            <div class="aspect-square bg-gray-100 overflow-hidden">
                                @if($related->image_url)
                                    <img src="{{ $related->image_url }}"
                                         alt="{{ $related->name }}"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                         loading="lazy"
                                         onerror="this.src='{{ asset('images/default-product.svg') }}'">
                                @else
                                    <img src="{{ asset('images/default-product.svg') }}"
                                         alt="{{ $related->name }}"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                         loading="lazy">
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-yellow-600 transition-colors">
                                    {{ $related->name }}
                                </h3>
                                <div class="flex items-center justify-between">
                                    <p class="text-yellow-600 font-bold text-lg">{{ number_format($related->price, 0, ',', ' ') }} GNF</p>
                                    @if($related->stock > 0)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            En stock
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var button = document.getElementById('share-product-button');
        if (!button) {
            return;
        }

        button.addEventListener('click', async function () {
            var url = button.getAttribute('data-share-url') || window.location.href;
            var title = button.getAttribute('data-share-title') || document.title;

            if (navigator.share) {
                try {
                    await navigator.share({ title: title, url: url });
                } catch (error) {
                    // User cancelled or share failed; no UI needed.
                }
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(url);
                    alert('Lien copié dans le presse-papiers.');
                    return;
                } catch (error) {
                    // Fallback below.
                }
            }

            var tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Lien copié dans le presse-papiers.');
        });
    });
</script>
@endpush
