{{-- resources/views/vendeur/profile/index.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Ma Boutique - Espace Vendeur')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- En-tête --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ma Boutique</h1>
            <p class="text-gray-600 mt-1">Gérez les informations de votre boutique</p>
        </div>
        
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="{{ route('vendeur.profile.edit') }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fas fa-edit"></i>
                <span>Modifier</span>
            </a>
            <a href="{{ route('vendeur.profile.password.edit') }}" 
               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition flex items-center space-x-2">
                <i class="fas fa-key"></i>
                <span>Mot de passe</span>
            </a>
        </div>
    </div>

    @if($vendor)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Carte principale boutique --}}
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    {{-- Header avec logo --}}
                    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-6">
                        <div class="flex items-center space-x-4">
                            @if($vendor->image_url)
                                <img src="{{ $vendor->image_url }}" 
                                     alt="{{ $vendor->shop_name }}" 
                                     class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-lg">
                            @else
                                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-store text-white text-2xl"></i>
                                </div>
                            @endif
                            <div class="text-white">
                                <h2 class="text-2xl font-bold">{{ $vendor->shop_name }}</h2>
                                <p class="text-yellow-100">Vendeur depuis {{ $vendor->created_at->format('F Y') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Contenu --}}
                    <div class="p-6">
                        {{-- Description --}}
                        @if($vendor->description)
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Description</h3>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $vendor->description }}</p>
                            </div>
                        @endif

                        {{-- Statistiques boutique --}}
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiques de la boutique</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ $vendor->products()->count() }}
                                    </div>
                                    <div class="text-sm text-gray-600">Produits</div>
                                </div>
                                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                    <div class="text-2xl font-bold text-yellow-600">
                                        {{ $vendor->products()->where('status', 'active')->count() }}
                                    </div>
                                    <div class="text-sm text-gray-600">Actifs</div>
                                </div>
                                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                    <div class="text-2xl font-bold text-yellow-600">
                                        {{ $vendor->vendorOrders()->count() }}
                                    </div>
                                    <div class="text-sm text-gray-600">Commandes</div>
                                </div>
                                <div class="text-center p-4 bg-orange-50 rounded-lg">
                                    <div class="text-2xl font-bold text-orange-600">
                                        {{ number_format($vendor->vendorOrders()->where('status', 'delivered')->sum('total_amount'), 0, ',', ' ') }}
                                    </div>
                                    <div class="text-sm text-gray-600">CA (GNF)</div>
                                </div>
                            </div>
                        </div>

                        {{-- Informations contact --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de contact</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email</label>
                                    <p class="text-gray-900">{{ auth()->user()->email }}</p>
                                </div>
                                @if(auth()->user()->phone)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Téléphone</label>
                                        <p class="text-gray-900">{{ auth()->user()->phone }}</p>
                                    </div>
                                @endif
                                @if(auth()->user()->delivery_address)
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-medium text-gray-500">Adresse</label>
                                        <p class="text-gray-900">{{ auth()->user()->delivery_address }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar informations -->
            <div class="space-y-6">
                {{-- Statut boutique --}}
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Statut de la boutique</h3>
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-3
                            @if($vendor->status === 'approved') bg-yellow-100
                            @elseif($vendor->status === 'pending') bg-yellow-100
                            @else bg-red-100
                            @endif">
                            <i class="fas 
                                @if($vendor->status === 'approved') fa-check text-yellow-600 text-2xl
                                @elseif($vendor->status === 'pending') fa-clock text-yellow-600 text-2xl
                                @else fa-times text-red-600 text-2xl
                                @endif"></i>
                        </div>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                            @if($vendor->status === 'approved') bg-yellow-100 text-yellow-800
                            @elseif($vendor->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            @if($vendor->status === 'approved') Approuvée
                            @elseif($vendor->status === 'pending') En attente de validation
                            @else Suspendue
                            @endif
                        </span>
                    </div>
                    
                    @if($vendor->status === 'pending')
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                Votre boutique est en attente de validation par l'administrateur.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Actions rapides --}}
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Actions rapides</h3>
                    <div class="space-y-3">
                        <a href="{{ route('vendeur.products.create') }}" 
                           class="w-full px-4 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition flex items-center justify-center space-x-2">
                            <i class="fas fa-plus"></i>
                            <span>Ajouter un produit</span>
                        </a>
                        
                        <a href="{{ route('vendeur.orders.index') }}" 
                           class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center justify-center space-x-2">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Voir les commandes</span>
                        </a>
                        
                        <a href="{{ route('vendeur.profile.edit') }}" 
                           class="w-full px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center justify-center space-x-2">
                            <i class="fas fa-edit"></i>
                            <span>Modifier la boutique</span>
                        </a>
                    </div>
                </div>

                {{-- Informations système --}}
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations système</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">ID Boutique:</span>
                            <span class="text-gray-900">#{{ $vendor->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Créée le:</span>
                            <span class="text-gray-900">{{ $vendor->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Dernière mise à jour:</span>
                            <span class="text-gray-900">{{ $vendor->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Propriétaire:</span>
                            <span class="text-gray-900">{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Pas de boutique -->
        <div class="text-center py-12">
            <div class="max-w-md mx-auto">
                <div class="flex items-center justify-center w-20 h-20 bg-gray-200 rounded-full mx-auto mb-6">
                    <i class="fas fa-store text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">Vous n'avez pas encore de boutique</h3>
                <p class="text-gray-600 mb-8">
                    Créez votre boutique pour commencer à vendre vos produits sur {{ config('app_name.name') }}
                </p>
                <a href="{{ route('vendeur.profile.create') }}" 
                   class="bg-yellow-600 text-white px-6 py-3 rounded-lg hover:bg-yellow-700 transition inline-flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Créer ma boutique</span>
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
