@extends('vendeur.layouts.app')

@section('title', 'Mes Produits - Espace Vendeur')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- GUIDE VENDEUR INTÉGRÉ -->
    @include('vendeur.components.guide')
    
    <!-- BARRE D'AMÉLIORÉE -->
    <div class="bg-gradient-to-r from-yellow-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-2">Mes Produits</h1>
                <p class="text-yellow-100">Gérez votre catalogue et développez vos ventes</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
                <a href="{{ route('vendeur.products.create') }}" class="group bg-white text-yellow-600 px-6 py-3 rounded-xl font-semibold hover:bg-yellow-50 transition-all transform hover:scale-105 shadow-lg">
                    <span class="flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter un produit
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </span>
                </a>
                <div class="relative">
                    <input type="text" placeholder="🔍 Rechercher un produit..." class="bg-white/20 backdrop-blur-sm text-white placeholder-yellow-200 px-4 py-3 rounded-xl w-64 focus:outline-none focus:bg-white/30 transition-colors">
                </div>
            </div>
        </div>
    </div>

    <!-- BARRE DE FILTRES AMÉLIORÉE -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900 flex items-center">
                <i class="fas fa-filter text-blue-500 mr-3"></i>
                Filtres rapides
            </h3>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    Tous ({{ $products->count() }})
                </button>
                <button class="px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-orange-500 transition-colors">
                    Actifs ({{ $products->where('status', 'active')->count() }})
                </button>
                <button class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-600 transition-colors">
                    Stock faible ({{ $lowStockProducts ?? 0 }})
                </button>
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                    Rupture ({{ $outOfStockProducts ?? 0 }})
                </button>
            </div>
        </div>
    </div>

    <!-- ZONE VIDE INTELLIGENTE -->
    @if($products->count() == 0)
    <div class="bg-white rounded-2xl shadow-lg p-12 text-center border border-gray-100">
        <div class="w-24 h-24 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-box-open text-gray-400 text-4xl"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-4">Aucun produit pour le moment</h3>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">
            Commencez votre adventure vendeur en ajoutant votre premier produit. 
            C'est simple, rapide et gratuit !
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('vendeur.products.create') }}" class="group bg-gradient-to-r from-yellow-500 to-orange-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-yellow-600 hover:to-orange-700 transition-all transform hover:scale-105 shadow-xl">
                <span class="flex items-center">
                    <i class="fas fa-rocket mr-3"></i>
                    Ajouter mon premier produit
                    <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                </span>
            </a>
            <a href="{{ route('vendeur.profile.create') }}" class="px-8 py-4 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
                <i class="fas fa-info-circle mr-2"></i>
                En savoir plus
            </a>
        </div>
        
        <!-- Conseils rapides -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl mx-auto">
            <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                <div class="flex items-center mb-2">
                    <i class="fas fa-camera text-blue-600 mr-2"></i>
                    <span class="text-sm font-medium text-blue-900">Photo de qualité</span>
                </div>
                <p class="text-xs text-blue-700">Une bonne photo augmente les ventes de 40%</p>
            </div>
            <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-200">
                <div class="flex items-center mb-2">
                    <i class="fas fa-tag text-yellow-600 mr-2"></i>
                    <span class="text-sm font-medium text-yellow-900">Prix compétitif</span>
                </div>
                <p class="text-xs text-yellow-700">Regardez les prix des concurrents</p>
            </div>
            <div class="bg-orange-50 rounded-xl p-4 border border-orange-200">
                <div class="flex items-center mb-2">
                    <i class="fas fa-align-left text-orange-600 mr-2"></i>
                    <span class="text-sm font-medium text-orange-900">Description détaillée</span>
                </div>
                <p class="text-xs text-orange-700">Plus d'infos = plus de confiance</p>
            </div>
        </div>
    </div>
    @else
    <!-- CARTE PRODUITS AMÉLIORÉES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($products as $product)
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-100 overflow-hidden group">
                <!-- Image produit -->
                <div class="aspect-square bg-gray-100 relative overflow-hidden">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" 
                             alt="{{ $product->name }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-200">
                            <i class="fas fa-image text-gray-400 text-3xl"></i>
                        </div>
                    @endif
                    
                    <!-- Badges statut -->
                    <div class="absolute top-2 right-2 flex gap-2">
                        @if($product->stock <= 0)
                            <span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                Rupture
                            </span>
                        @elseif($product->stock <= 5)
                            <span class="bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                Stock faible
                            </span>
                        @endif
                        
                        @if($product->status == 'active')
                            <span class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                Actif
                            </span>
                        @else
                            <span class="bg-gray-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                                Inactif
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- Contenu produit -->
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ $product->name }}</h3>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $product->description ?? 'Pas de description' }}</p>
                    
                    <!-- Prix et stock -->
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-xl font-bold text-yellow-600">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                            <p class="text-xs text-gray-500">{{ $product->category->name ?? 'Non catégorisé' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold {{ $product->stock <= 0 ? 'text-red-600' : ($product->stock <= 5 ? 'text-orange-600' : 'text-gray-600') }}">
                                {{ $product->stock }} en stock
                            </p>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('vendeur.products.show', $product) }}" 
                           class="flex-1 bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors text-center">
                            <i class="fas fa-eye mr-1"></i>
                            Voir
                        </a>
                        <a href="{{ route('vendeur.products.edit', $product) }}" 
                           class="flex-1 bg-gray-600 text-white px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors text-center">
                            <i class="fas fa-edit mr-1"></i>
                            Modifier
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
