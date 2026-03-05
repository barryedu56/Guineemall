@extends('vendeur.layouts.app')

@section('title', 'Ma Boutique - ' . config('app_name.name'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- HEADER BOUTIQUE AMÉLIORÉ -->
    <div class="bg-gradient-to-r from-yellow-500 to-orange-600 rounded-2xl p-8 text-white shadow-xl mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-center mb-4 md:mb-0">
                <!-- Logo Boutique -->
                @if($vendor->image_url)
                    <img src="{{ $vendor->image_url }}" alt="Logo {{ $vendor->shop_name }}" class="w-20 h-20 object-cover rounded-xl mr-6">
                @else
                    <div class="w-20 h-20 bg-white/20 rounded-xl flex items-center justify-center mr-6">
                        <i class="fas fa-store text-white text-3xl"></i>
                    </div>
                @endif
                <div>
                    <div class="flex items-center mb-2">
                        <h1 class="text-3xl font-bold">{{ $vendor->shop_name }}</h1>
                        <span class="ml-3 px-3 py-1 rounded-full text-sm font-semibold {{ $vendor->status == 'approved' ? 'bg-yellow-500 text-white' : 'bg-orange-500 text-white' }}">
                            {{ $vendor->status == 'approved' ? 'Validée' : 'En attente' }}
                        </span>
                    </div>
                    <p class="text-yellow-100">{{ $vendor->description ?? 'Votre boutique sur ' . config('app_name.name') }}</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('vendeur.dashboard') }}" class="bg-white/20 backdrop-blur-sm text-white px-6 py-3 rounded-xl font-semibold hover:bg-white/30 transition-all border border-white/30">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour au dashboard
                </a>
                <a href="{{ route('client.catalog.index', ['vendor' => $vendor->id]) }}" target="_blank" class="bg-white text-yellow-600 px-6 py-3 rounded-xl font-semibold hover:bg-yellow-50 transition-colors">
                    <i class="fas fa-eye mr-2"></i>
                    Voir boutique client
                </a>
            </div>
        </div>
    </div>

    <!-- GUIDE VENDEUR INTÉGRÉ -->
    @include('vendeur.components.guide')

    <!-- SECTION APERÇU PUBLIC -->
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-8 border border-yellow-200 mb-8">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                <i class="fas fa-eye text-yellow-600 text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Aperçu public de votre boutique</h2>
                <p class="text-gray-600">Voici comment votre boutique apparaît aux clients sur {{ config('app_name.name') }}</p>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <!-- Un seul logo ici -->
                    @if($vendor->image_url)
                        <img src="{{ $vendor->image_url }}" alt="Logo {{ $vendor->shop_name }}" class="w-12 h-12 object-cover rounded-lg mr-3">
                    @else
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-store text-gray-400"></i>
                        </div>
                    @endif
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $vendor->shop_name }}</h3>
                        <p class="text-sm text-gray-500">{{ $products->count() }} produits</p>
                    </div>
                </div>
                <a href="{{ route('client.catalog.index', ['vendor' => $vendor->id]) }}" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">
                    Voir boutique client
                </a>
            </div>
            <div class="text-sm text-gray-600">
                <p class="mb-2">📍 {{ $vendor->address ?? 'Conakry, Guinée' }}</p>
                <p>⭐ 4.8 ({{ rand(50, 200) }} avis)</p>
            </div>
        </div>
    </div>

    <!-- SECTION QUALITÉ BOUTIQUE -->
    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100 mb-8">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                <i class="fas fa-check-double text-yellow-600 text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Qualité de votre boutique</h2>
                <p class="text-gray-600">Complétez ces éléments pour attirer plus de clients</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="border rounded-xl p-4 {{ $vendor->logo ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200 bg-gray-50' }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="fas fa-image {{ $vendor->logo ? 'text-yellow-600' : 'text-gray-400' }} mr-2"></i>
                        <span class="font-medium text-gray-900">Logo boutique</span>
                    </div>
                    <span class="text-2xl">{{ $vendor->logo ? '✅' : '❌' }}</span>
                </div>
                <p class="text-sm text-gray-600">{{ $vendor->logo ? 'Logo ajouté' : 'Ajoutez votre logo' }}</p>
                @if(!$vendor->logo)
                <a href="{{ route('vendeur.profile.edit') }}" class="mt-2 text-blue-600 text-sm font-medium hover:text-blue-700">
                    Ajouter un logo →
                </a>
                @endif
            </div>
            
            <div class="border rounded-xl p-4 {{ $vendor->description ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200 bg-gray-50' }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="fas fa-align-left {{ $vendor->description ? 'text-yellow-600' : 'text-gray-400' }} mr-2"></i>
                        <span class="font-medium text-gray-900">Description</span>
                    </div>
                    <span class="text-2xl">{{ $vendor->description ? '✅' : '❌' }}</span>
                </div>
                <p class="text-sm text-gray-600">{{ $vendor->description ? 'Description complète' : 'Décrivez votre boutique' }}</p>
                @if(!$vendor->description)
                <a href="{{ route('vendeur.profile.edit') }}" class="mt-2 text-blue-600 text-sm font-medium hover:text-blue-700">
                    Ajouter description →
                </a>
                @endif
            </div>
            
            <div class="border rounded-xl p-4 {{ $products->where('status', 'active')->count() > 0 ? 'border-yellow-200 bg-yellow-50' : 'border-gray-200 bg-gray-50' }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="fas fa-box {{ $products->where('status', 'active')->count() > 0 ? 'text-yellow-600' : 'text-gray-400' }} mr-2"></i>
                        <span class="font-medium text-gray-900">Produits actifs</span>
                    </div>
                    <span class="text-2xl">{{ $products->where('status', 'active')->count() > 0 ? '✅' : '❌' }}</span>
                </div>
                <p class="text-sm text-gray-600">{{ $products->where('status', 'active')->count() > 0 ? $products->where('status', 'active')->count() . ' produits actifs' : 'Activez vos produits' }}</p>
                @if($products->where('status', 'active')->count() == 0)
                <a href="{{ route('vendeur.products.index') }}" class="mt-2 text-blue-600 text-sm font-medium hover:text-blue-700">
                    Gérer produits →
                </a>
                @endif
            </div>
        </div>
    </div>
    <!-- SECTION ACTIONS RECOMMANDÉES -->
    <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-2xl p-8 border border-orange-200 mb-8">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mr-4">
                <i class="fas fa-rocket text-orange-600 text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">Actions recommandées</h2>
                <p class="text-gray-600">Accélérez votre succès avec ces actions prioritaires</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('vendeur.profile.edit') }}" class="group bg-white rounded-xl p-6 border border-gray-200 hover:border-orange-300 hover:shadow-lg transition-all">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-edit text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">Modifier boutique</h3>
                </div>
                <p class="text-sm text-gray-600">Améliorez vos informations pour attirer plus de clients</p>
                <div class="mt-4 text-orange-600 text-sm font-medium group-hover:text-orange-700 transition-colors">
                    Modifier →
                </div>
            </a>
            
            <a href="{{ route('vendeur.products.create') }}" class="group bg-white rounded-xl p-6 border border-gray-200 hover:border-orange-300 hover:shadow-lg transition-all">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-plus text-yellow-600 text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-yellow-600 transition-colors">Ajouter produit</h3>
                </div>
                <p class="text-sm text-gray-600">Augmentez votre catalogue avec de nouveaux produits</p>
                <div class="mt-4 text-yellow-600 text-sm font-medium group-hover:text-yellow-700 transition-colors">
                    Ajouter →
                </div>
            </a>
            
            <a href="{{ route('vendeur.profile.index') }}" class="group bg-white rounded-xl p-6 border border-gray-200 hover:border-orange-300 hover:shadow-lg transition-all">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">Compléter infos</h3>
                </div>
                <p class="text-sm text-gray-600">Ajoutez votre logo, description et coordonnées</p>
                <div class="mt-4 text-blue-600 text-sm font-medium group-hover:text-blue-700 transition-colors">
                    Compléter →
                </div>
            </a>
        </div>
    </div>

    <!-- Statistiques Boutique -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-box text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total produits</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $products->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Produits actifs</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $products->where('status', 'active')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-star text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Note moyenne</p>
                    <p class="text-2xl font-bold text-gray-900">4.8</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits de la boutique -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-shopping-bag text-blue-500 mr-3"></i>
                Mes produits
            </h2>
            <a href="{{ route('vendeur.products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Ajouter un produit
            </a>
        </div>

        @if($products->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($products as $product)
                    <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                        <div class="aspect-square bg-gray-200 rounded-lg mb-4 overflow-hidden">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-3xl"></i>
                                </div>
                            @endif
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">{{ $product->name }}</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ Str::limit($product->description, 80) }}</p>
                        <div class="flex items-center justify-between">
                            <p class="text-lg font-bold text-yellow-600">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $product->status == 'active' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $product->status == 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('vendeur.products.edit', $product) }}" class="flex-1 text-center bg-blue-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ route('vendeur.products.show', $product) }}" class="flex-1 text-center bg-gray-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-gray-700 transition-colors">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-box-open text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun produit</h3>
                <p class="text-gray-600 mb-6">Commencez par ajouter vos premiers produits</p>
                <a href="{{ route('vendeur.products.create') }}" class="bg-yellow-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-yellow-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter mon premier produit
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
