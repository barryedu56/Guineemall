{{-- resources/views/client/dashboard.blade.php --}}
@extends('client.layout')

@section('title', 'Accueil - GuinéeMall')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Hero Banner Amélioré --}}
    <div class="bg-gradient-to-r from-green-500 to-green-700 rounded-2xl overflow-hidden mb-8 relative">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="grid md:grid-cols-2 gap-8 items-center p-8 relative z-10">
            <div class="text-white">
                <h1 class="text-4xl md:text-5xl font-black mb-4 leading-tight">
                    Tout <span class="text-yellow-300">le marché</span><br>
                    en <span class="text-yellow-300">ligne</span>, à portée de main
                </h1>
                <p class="text-lg md:text-xl mb-6 text-yellow-50 leading-relaxed">
                    Découvrez des milliers de produits locaux et internationaux<br>
                    <span class="text-yellow-200 font-semibold">Livraison rapide partout en Guinée</span>
                </p>
                <a href="{{ route('client.catalog.index') }}" 
                   class="inline-flex items-center bg-white text-yellow-600 px-8 py-4 rounded-full font-bold hover:bg-yellow-50 transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    Découvrir les produits
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <div class="hidden md:block relative">
                <!-- Carte produit en vedette -->
                <div class="bg-white rounded-xl shadow-2xl p-4 transform hover:scale-105 transition-transform">
                    <div class="relative">
                        <img src="/images/hero-products.jpg" 
                             alt="Produits vedettes" 
                             class="w-full h-64 object-cover rounded-lg"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%2348bb78\' width=\'400\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' fill=\'white\' font-size=\'20\' dy=\'.3em\'%3EProduits GuinéeMall%3C/text%3E%3C/svg%3E'">
                        <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            -30%
                        </div>
                    </div>
                    <div class="mt-3">
                        <h3 class="font-bold text-gray-900">Produits du moment</h3>
                        <p class="text-sm text-gray-600">Meilleures ventes cette semaine</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-lg font-bold text-yellow-600">25 000 GNF</span>
                            <span class="text-sm text-gray-400 line-through">35 000 GNF</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Catégories Populaires --}}
    <section class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Catégories Populaires</h2>
            <a href="{{ route('client.catalog.index') }}" class="text-yellow-600 hover:text-yellow-700 font-semibold flex items-center">
                Voir tout
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <a href="{{ route('client.catalog.index') }}?category=electronique" class="group">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all p-4 text-center cursor-pointer group-hover:scale-105">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-blue-200">
                        <i class="fas fa-laptop text-blue-600 text-lg"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">Électronique</h3>
                </div>
            </a>
            <a href="{{ route('client.catalog.index') }}?category=mode" class="group">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all p-4 text-center cursor-pointer group-hover:scale-105">
                    <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-pink-200">
                        <i class="fas fa-tshirt text-pink-600 text-lg"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">Mode</h3>
                </div>
            </a>
            <a href="{{ route('client.catalog.index') }}?category=maison" class="group">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all p-4 text-center cursor-pointer group-hover:scale-105">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-yellow-200">
                        <i class="fas fa-home text-yellow-600 text-lg"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">Maison</h3>
                </div>
            </a>
            <a href="{{ route('client.catalog.index') }}?category=beaute" class="group">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all p-4 text-center cursor-pointer group-hover:scale-105">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-orange-200">
                        <i class="fas fa-spa text-orange-600 text-lg"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">Beauté</h3>
                </div>
            </a>
            <a href="{{ route('client.catalog.index') }}?category=sport" class="group">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all p-4 text-center cursor-pointer group-hover:scale-105">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-orange-200">
                        <i class="fas fa-dumbbell text-orange-600 text-lg"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">Sport</h3>
                </div>
            </a>
            <a href="{{ route('client.catalog.index') }}?category=alimentation" class="group">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all p-4 text-center cursor-pointer group-hover:scale-105">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:bg-yellow-200">
                        <i class="fas fa-utensils text-yellow-600 text-lg"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900">Alimentation</h3>
                </div>
            </a>
        </div>
    </section>

    {{-- Produits en Vedette --}}
    <section class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Produits en Vedette</h2>
            <a href="{{ route('client.catalog.index') }}" class="text-yellow-600 hover:text-yellow-700 font-semibold flex items-center">
                Voir tout
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Produit 1 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all group">
                <div class="relative">
                    <img src="https://via.placeholder.com/300x200/48bb78/ffffff?text=Produit+1" 
                         alt="Produit vedette" 
                         class="w-full h-48 object-cover rounded-t-xl"
                         loading="lazy">
                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        -25%
                    </div>
                    <div class="absolute top-2 left-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        Nouveau
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-1 group-hover:text-yellow-600">Smartphone Premium</h3>
                    <p class="text-sm text-gray-600 mb-2">Vendeur: TechStore</p>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-lg font-bold text-yellow-600">450 000 GNF</span>
                            <span class="text-sm text-gray-400 line-through">600 000 GNF</span>
                        </div>
                        <div class="flex items-center text-yellow-400 text-xs">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span class="text-gray-600 ml-1">(4.5)</span>
                        </div>
                    </div>
                    <button class="w-full bg-yellow-600 text-white py-2 rounded-lg font-semibold hover:bg-yellow-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-cart-plus mr-2"></i>
                        Ajouter au panier
                    </button>
                </div>
            </div>

            <!-- Produit 2 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all group">
                <div class="relative">
                    <img src="https://via.placeholder.com/300x200/48bb78/ffffff?text=Produit+2" 
                         alt="Produit vedette" 
                         class="w-full h-48 object-cover rounded-t-xl"
                         loading="lazy">
                    <div class="absolute top-2 left-2 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        Populaire
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-1 group-hover:text-yellow-600">Montre Connectée</h3>
                    <p class="text-sm text-gray-600 mb-2">Vendeur: WatchZone</p>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-lg font-bold text-yellow-600">125 000 GNF</span>
                        </div>
                        <div class="flex items-center text-yellow-400 text-xs">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                            <span class="text-gray-600 ml-1">(4.0)</span>
                        </div>
                    </div>
                    <button class="w-full bg-yellow-600 text-white py-2 rounded-lg font-semibold hover:bg-yellow-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-cart-plus mr-2"></i>
                        Ajouter au panier
                    </button>
                </div>
            </div>

            <!-- Produit 3 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all group">
                <div class="relative">
                    <img src="https://via.placeholder.com/300x200/48bb78/ffffff?text=Produit+3" 
                         alt="Produit vedette" 
                         class="w-full h-48 object-cover rounded-t-xl"
                         loading="lazy">
                    <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        -15%
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-1 group-hover:text-yellow-600">Casque Audio Pro</h3>
                    <p class="text-sm text-gray-600 mb-2">Vendeur: AudioWorld</p>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-lg font-bold text-yellow-600">85 000 GNF</span>
                            <span class="text-sm text-gray-400 line-through">100 000 GNF</span>
                        </div>
                        <div class="flex items-center text-yellow-400 text-xs">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span class="text-gray-600 ml-1">(5.0)</span>
                        </div>
                    </div>
                    <button class="w-full bg-yellow-600 text-white py-2 rounded-lg font-semibold hover:bg-yellow-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-cart-plus mr-2"></i>
                        Ajouter au panier
                    </button>
                </div>
            </div>

            <!-- Produit 4 -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all group">
                <div class="relative">
                    <img src="https://via.placeholder.com/300x200/48bb78/ffffff?text=Produit+4" 
                         alt="Produit vedette" 
                         class="w-full h-48 object-cover rounded-t-xl"
                         loading="lazy">
                    <div class="absolute top-2 left-2 bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        Exclusif
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-1 group-hover:text-yellow-600">Sac à Main</h3>
                    <p class="text-sm text-gray-600 mb-2">Vendeur: FashionHub</p>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-lg font-bold text-yellow-600">75 000 GNF</span>
                        </div>
                        <div class="flex items-center text-yellow-400 text-xs">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                            <span class="text-gray-600 ml-1">(4.2)</span>
                        </div>
                    </div>
                    <button class="w-full bg-yellow-600 text-white py-2 rounded-lg font-semibold hover:bg-yellow-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-cart-plus mr-2"></i>
                        Ajouter au panier
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- Dashboard Client (Aperçu Rapide) --}}
    <section class="mb-12">
        <h2 class="text-2xl font-bold mb-6 text-gray-900">Mon Espace Client</h2>
        
        {{-- Statistiques rapides --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-shopping-bag text-yellow-600 text-xl"></i>
                    <span class="text-xs text-gray-500">Total</span>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total_orders'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Commandes</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    <span class="text-xs text-gray-500">En attente</span>
                </div>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending_orders'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Commandes</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-check-circle text-yellow-600 text-xl"></i>
                    <span class="text-xs text-gray-500">Livrées</span>
                </div>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['delivered_orders'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Commandes</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                    <span class="text-xs text-gray-500">Panier</span>
                </div>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['cart_items'] ?? 0 }}</p>
                <p class="text-sm text-gray-600">Articles</p>
            </div>
        </div>

        <section class="mb-12">
            <x-notification-panel title="Mes notifications" />
        </section>

        {{-- Activité récente --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Commandes récentes</h3>
                    <a href="{{ route('client.orders.index') }}" class="text-yellow-600 hover:text-yellow-700 font-semibold text-sm flex items-center">
                        Voir tout
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                @if($recentOrders->isEmpty())
                    <p class="text-gray-600 text-center py-4">Aucune commande récente.</p>
                @else
                    <div class="space-y-3">
                        @foreach($recentOrders as $order)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div>
                                    <p class="font-semibold text-gray-900">#{{ $order->id }}</p>
                                    <p class="text-sm text-gray-600">{{ $order->created_at->format('d/m/Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-yellow-600">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</p>
                                    <span class="text-xs px-2 py-1 rounded-full
                                        {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $order->status === 'delivered' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Panier récent</h3>
                    <a href="{{ route('client.cart.index') }}" class="text-yellow-600 hover:text-yellow-700 font-semibold text-sm flex items-center">
                        Voir panier
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                @if($cartItems->isEmpty())
                    <p class="text-gray-600 text-center py-4">Votre panier est vide.</p>
                @else
                    <div class="space-y-3">
                        @foreach($cartItems as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-10 h-10 rounded object-cover" loading="lazy">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $item->product->name }}</p>
                                        <p class="text-xs text-gray-600">{{ $item->quantity }} x {{ number_format($item->product->price, 0, ',', ' ') }} GNF</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-yellow-600">{{ number_format($item->quantity * $item->product->price, 0, ',', ' ') }} GNF</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection