@extends('vendeur.layouts.app')

@section('title', 'Tableau de bord Vendeur')

@section('content')
<div class="space-y-6">
    <!-- HEADER VENDEUR AMÉLIORÉ -->
    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 rounded-2xl p-8 text-white shadow-xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-6 md:mb-0">
                <h1 class="text-3xl font-bold mb-2">
                    Bonjour {{ Auth::user()->vendor->shop_name ?? 'Vendeur' }} 👋
                </h1>
                <p class="text-yellow-100 text-lg">
                    Gérez votre boutique et développez vos ventes sur {{ config('app_name.name') }}
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('vendeur.products.create') }}" class="group px-6 py-3 bg-white text-yellow-600 rounded-xl font-semibold hover:bg-yellow-50 transition-all transform hover:scale-105 shadow-lg">
                    <span class="flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter un produit
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </span>
                </a>
                <a href="{{ route('vendeur.shop.show') }}" class="px-6 py-3 bg-yellow-700 text-white rounded-xl font-semibold hover:bg-yellow-800 transition-all border border-yellow-500">
                    <i class="fas fa-store mr-2"></i>
                    Voir ma boutique
                </a>
            </div>
        </div>
    </div>

    <!-- CARTES STATISTIQUES UNIFORMISÉES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total produits -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-box text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total produits</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalProducts }}</p>
                    <p class="text-xs text-gray-400 mt-1">Dans votre catalogue</p>
                </div>
            </div>
        </div>

        <!-- Produits actifs -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Produits actifs</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $activeProducts }}</p>
                    <p class="text-xs text-gray-400 mt-1">Visibles par les clients</p>
                </div>
            </div>
        </div>

        <!-- Commandes en attente -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-14 h-14 {{ $pendingOrders > 0 ? 'bg-orange-100' : 'bg-gray-100' }} rounded-xl flex items-center justify-center">
                    <i class="fas {{ $pendingOrders > 0 ? 'text-orange-600' : 'text-gray-600' }} text-xl fa-clock"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Commandes en attente</p>
                    <p class="text-2xl font-bold {{ $pendingOrders > 0 ? 'text-orange-600' : 'text-gray-900' }}">{{ $pendingOrders }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $pendingOrders > 0 ? 'À traiter' : 'Rien en attente' }}</p>
                </div>
            </div>
        </div>

        <!-- Chiffre d'affaires -->
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Chiffre d'affaires</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalRevenue, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-400 mt-1">GNF total</p>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION ACTIONS PRIORITAIRES -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-bullhorn text-orange-500 mr-3"></i>
            Actions prioritaires
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Si aucun produit -->
            @if($totalProducts == 0)
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-plus-circle text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Commencez à vendre</h3>
                        <p class="text-sm text-gray-600">Ajoutez votre premier produit</p>
                    </div>
                </div>
                <a href="{{ route('vendeur.products.create') }}" class="w-full bg-blue-600 text-white rounded-lg py-2 px-4 text-center font-semibold hover:bg-blue-700 transition-colors">
                    Ajouter mon premier produit
                </a>
            </div>
            @endif
            
            <!-- Si aucune commande -->
            @if($totalOrders == 0)
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-shopping-cart text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Prêt pour les ventes</h3>
                        <p class="text-sm text-gray-600">Vos premières commandes arrivent bientôt</p>
                    </div>
                </div>
                <div class="text-center">
                    <p class="text-sm text-yellow-700 font-medium">🎉 Votre boutique est prête !</p>
                </div>
            </div>
            @endif
            
            <!-- Si stock faible -->
            @if($lowStockProducts->count() > 0)
            <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-6 border border-orange-200">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Stock faible</h3>
                        <p class="text-sm text-gray-600">{{ $lowStockProducts->count() }} produit(s) à réapprovisionner</p>
                    </div>
                </div>
                <a href="{{ route('vendeur.products.index') }}" class="w-full bg-orange-600 text-white rounded-lg py-2 px-4 text-center font-semibold hover:bg-orange-700 transition-colors">
                    Gérer mon stock
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- NOTIFICATIONS VENDEUR AMÉLIORÉES -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-bell text-orange-500 mr-3"></i>
            Notifications et conseils
        </h2>
        
        <div class="space-y-4">
            <!-- Message de bienvenue si nouveau vendeur -->
            @if($totalOrders == 0)
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                <div class="flex items-start">
                    <div class="w-16 h-16 bg-yellow-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-rocket text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-2">{{ config('app_name.spaces.vendor.welcome_text') }} 🎉</h3>
                        <p class="text-gray-600 mb-4">
                            Votre boutique est maintenant active. Voici nos conseils pour démarrer :
                        </p>
                        <ul class="text-sm text-gray-600 space-y-2 mb-4">
                            <li class="flex items-center">
                                <i class="fas fa-check text-yellow-500 mr-2"></i>
                                Ajoutez des produits avec des photos de qualité
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-yellow-500 mr-2"></i>
                                Décrivez vos produits en détail
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-yellow-500 mr-2"></i>
                                Fixez des prix compétitifs
                            </li>
                        </ul>
                        <a href="{{ route('vendeur.products.create') }}" class="inline-flex items-center bg-yellow-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-yellow-600 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter mon premier produit
                        </a>
                    </div>
                </div>
            </div>
            @else
            <!-- Message pour vendeurs actifs -->
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                <div class="flex items-start">
                    <div class="w-16 h-16 bg-yellow-100 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                        <i class="fas fa-chart-line text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-2">Excellente performance ! 📈</h3>
                        <p class="text-gray-600 mb-4">
                            Continuez comme ça ! Vos clients apprécient vos produits.
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-yellow-600">{{ $deliveredOrders }}</p>
                                <p class="text-sm text-gray-600">Commandes livrées</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-yellow-600">{{ $deliveredOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0 }}%</p>
                                <p class="text-sm text-gray-600">Taux de livraison</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- DERNIÈRES COMMANDES AMÉLIORÉES -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-shopping-bag text-blue-500 mr-3"></i>
                    Dernières commandes
                </h2>
                <a href="{{ route('vendeur.orders.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Voir tout <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            @if($recentOrders->count() > 0)
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Commande #{{ $order->id }}</p>
                                <p class="text-sm text-gray-600">{{ $order->order->user->name ?? 'Client' }}</p>
                                <p class="text-xs text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</p>
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full 
                                    {{ $order->status == 'delivered' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($order->status == 'pending' ? 'bg-orange-100 text-orange-800' : 
                                       'bg-gray-100 text-gray-800') }}">
                                    {{ $order->status == 'delivered' ? 'Livrée' : 
                                       ($order->status == 'pending' ? 'En attente' : $order->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-inbox text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 mb-4">Aucune commande pour le moment</p>
                    <p class="text-sm text-gray-400">Vos premières commandes apparaîtront ici</p>
                </div>
            @endif
        </div>

        <!-- STOCK FAIBLE AMÉLIORÉ -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-exclamation-triangle text-orange-500 mr-3"></i>
                    Stock faible
                </h2>
                <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-semibold">
                    {{ $lowStockProducts->count() }} produit(s)
                </span>
            </div>
            @if($lowStockProducts->count() > 0)
                <div class="space-y-3">
                    @foreach($lowStockProducts as $product)
                        <div class="flex items-center justify-between p-4 bg-orange-50 rounded-xl border border-orange-200">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                                <p class="text-sm text-gray-600">{{ $product->category->name ?? 'Non catégorisé' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-orange-600">{{ $product->stock }} en stock</p>
                                <p class="text-sm text-gray-500">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    <a href="{{ route('vendeur.products.index') }}" class="w-full bg-orange-600 text-white rounded-lg py-2 px-4 text-center font-semibold hover:bg-orange-700 transition-colors">
                        <i class="fas fa-box mr-2"></i>
                        Gérer mon stock
                    </a>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-yellow-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-yellow-600 text-2xl"></i>
                    </div>
                    <p class="text-yellow-600 font-medium mb-2">Stock optimal !</p>
                    <p class="text-sm text-gray-400">Tous vos produits ont un stock suffisant</p>
                </div>
            @endif
        </div>
    </div>

    <!-- STATISTIQUES SUPPLÉMENTAIRES AMÉLIORÉES -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Total commandes</h3>
                <i class="fas fa-shopping-cart text-yellow-500"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $totalOrders }}</p>
            <p class="text-sm text-yellow-600 mt-2">{{ $deliveredOrders }} livrées</p>
            <div class="mt-4 bg-yellow-50 rounded-lg p-2">
                <div class="flex items-center text-xs text-yellow-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ $deliveredOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0 }}% de taux de livraison
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Produits en rupture</h3>
                <i class="fas fa-exclamation-circle text-red-500"></i>
            </div>
            <p class="text-3xl font-bold {{ $outOfStockProducts > 0 ? 'text-red-600' : 'text-yellow-600' }}">{{ $outOfStockProducts }}</p>
            <p class="text-sm text-gray-500 mt-2">À réapprovisionner</p>
            @if($outOfStockProducts > 0)
            <div class="mt-4 bg-red-50 rounded-lg p-2">
                <div class="flex items-center text-xs text-red-700">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Action requise
                </div>
            </div>
            @else
            <div class="mt-4 bg-yellow-50 rounded-lg p-2">
                <div class="flex items-center text-xs text-yellow-700">
                    <i class="fas fa-check-circle mr-1"></i>
                    Stock optimal
                </div>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">Panier moyen</h3>
                <i class="fas fa-calculator text-orange-500"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900">
                {{ $deliveredOrders > 0 ? number_format($totalRevenue / $deliveredOrders, 0, ',', ' ') : 0 }}
            </p>
            <p class="text-sm text-gray-500 mt-2">GNF par commande</p>
            <div class="mt-4 bg-orange-50 rounded-lg p-2">
                <div class="flex items-center text-xs text-orange-700">
                    <i class="fas fa-chart-line mr-1"></i>
                    Performance moyenne
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
