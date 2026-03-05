@extends('layouts.public')

@section('title', 'Vendeurs Vérifiés - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Vendeurs Vérifiés</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="primary-yellow p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Vendeurs Vérifiés</h1>
            <p class="text-yellow-100 text-lg">Achetez en toute confiance</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Introduction -->
            <div class="mb-12">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Tous nos vendeurs passent par un processus de vérification rigoureux pour garantir 
                    leur authenticité et leur fiabilité. Achetez en toute sécurité avec des vendeurs 
                    de confiance sur GuinéeMall.
                </p>
            </div>

            <!-- Verification Process -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-id-card text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Vérification d'identité</h3>
                        <p class="text-gray-600">Nous vérifions l'identité de chaque vendeur.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-certificate text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Documents validés</h3>
                        <p class="text-gray-600">Nous validons tous les documents légaux.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-star text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Notation communautaire</h3>
                        <p class="text-gray-600">Les clients notent et évaluent les vendeurs.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Garantie de qualité</h3>
                        <p class="text-gray-600">Seuls les vendeurs qualifiés sont acceptés.</p>
                    </div>
                </div>
            </div>

            <!-- Trust Badges -->
            <div class="bg-gray-50 rounded-xl p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Badges de confiance</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-check-circle text-yellow-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Vendeur vérifié</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-award text-yellow-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Excellence</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-trophy text-yellow-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Top vendeur</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <i class="fas fa-heart text-yellow-600 text-2xl mb-2"></i>
                        <p class="text-sm text-gray-600">Clients satisfaits</p>
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
