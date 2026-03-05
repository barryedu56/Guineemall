@extends('layouts.public')

@section('title', 'Paiements Sécurisés - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Paiements Sécurisés</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="primary-yellow p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-alt text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Paiements Sécurisés</h1>
            <p class="text-yellow-100 text-lg">Votre sécurité est notre priorité absolue</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Introduction -->
            <div class="mb-12">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Chez GuinéeMall, nous protégeons chaque transaction avec les technologies de sécurité les plus avancées. 
                    Votre confiance est notre priorité, et nous mettons tout en œuvre pour garantir que vos paiements 
                    soient toujours sûrs et protégés.
                </p>
            </div>

            <!-- Security Features -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-lock text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Chiffrement SSL/TLS</h3>
                        <p class="text-gray-600">Toutes vos transactions sont protégées par un chiffrement de niveau bancaire.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-credit-card text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">3D Secure</h3>
                        <p class="text-gray-600">Authentification à deux facteurs pour une protection supplémentaire.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user-shield text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Protection des Données</h3>
                        <p class="text-gray-600">Vos informations bancaires ne sont jamais stockées sur nos serveurs.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Surveillance 24/7</h3>
                        <p class="text-gray-600">Notre équipe de sécurité surveille les transactions en temps réel.</p>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="bg-gray-50 rounded-xl p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Méthodes de paiement acceptées</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-mobile-alt text-2xl text-yellow-600 mb-2"></i>
                        <p class="text-sm text-gray-600">Mobile Money</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-credit-card text-2xl text-yellow-600 mb-2"></i>
                        <p class="text-sm text-gray-600">Cartes Visa/Mastercard</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-university text-2xl text-yellow-600 mb-2"></i>
                        <p class="text-sm text-gray-600">Virement bancaire</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-wallet text-2xl text-yellow-600 mb-2"></i>
                        <p class="text-sm text-gray-600">Portefeuilles électroniques</p>
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
