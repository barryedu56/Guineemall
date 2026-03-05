@extends('layouts.public')

@section('title', 'Livraison Rapide - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Livraison Rapide</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="primary-yellow p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-truck text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Livraison Rapide</h1>
            <p class="text-yellow-100 text-lg">Réception de vos commandes en 24-48h</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Introduction -->
            <div class="mb-12">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Chez GuinéeMall, nous comprenons que vous voulez recevoir vos commandes rapidement. 
                    C'est pourquoi nous avons mis en place un service de livraison rapide et fiable 
                    dans toute la Guinée.
                </p>
            </div>

            <!-- Delivery Features -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Livraison 24-48h</h3>
                        <p class="text-gray-600">Réception rapide à Conakry et principales villes.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-map-marked-alt text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Couverture nationale</h3>
                        <p class="text-gray-600">Nous livrons dans toute la Guinée.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shipping-fast text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Suivi en temps réel</h3>
                        <p class="text-gray-600">Suivez votre commande à chaque étape.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user-check text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Livraison confirmée</h3>
                        <p class="text-gray-600">Signature obligatoire à la réception.</p>
                    </div>
                </div>
            </div>

            <!-- Delivery Zones -->
            <div class="bg-gray-50 rounded-xl p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Zones de livraison</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-600 mb-2">Conakry (24h)</h4>
                        <p class="text-sm text-gray-600">Matoto, Ratoma, Dixinn, Kaloum</p>
                    </div>
                    <div class="bg-white rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-600 mb-2">Grandes villes (48h)</h4>
                        <p class="text-sm text-gray-600">Kindia, Labé, Kankan, Nzérékoré</p>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center bg-yellow-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-yellow-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
