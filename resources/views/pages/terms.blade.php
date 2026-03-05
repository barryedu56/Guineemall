@extends('layouts.public')

@section('title', $pageTitle ?? 'Conditions d\'utilisation - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Conditions d'utilisation</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-file-contract text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Conditions d'utilisation</h1>
            <p class="text-indigo-100 text-lg">Conditions générales d'utilisation de la plateforme GuinéeMall</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Statistiques Dynamiques -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Plateforme en temps réel</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ number_format($stats['total_products'] ?? 0) }}+</div>
                        <div class="text-sm text-gray-600">Produits actifs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ number_format($stats['total_vendors'] ?? 0) }}+</div>
                        <div class="text-sm text-gray-600">Vendeurs vérifiés</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-pink-600">{{ number_format($stats['total_users'] ?? 0) }}+</div>
                        <div class="text-sm text-gray-600">Clients satisfaits</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ $stats['satisfaction_rate'] ?? '98%' }}</div>
                        <div class="text-sm text-gray-600">Satisfaction</div>
                    </div>
                </div>
                <div class="mt-4 text-center text-sm text-gray-500">
                    Dernière mise à jour : {{ $lastUpdated ?? now()->format('d/m/Y') }} | Version {{ $version ?? '2.0.0' }}
                </div>
            </div>

            <!-- Introduction -->
            <div class="mb-8">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Bienvenue sur GuinéeMall, la marketplace N°1 en Guinée. En utilisant notre plateforme, 
                    vous acceptez les présentes conditions d'utilisation conçues pour garantir une expérience 
                    sécurisée et équitable pour tous nos utilisateurs.
                </p>
            </div>

            <!-- Sections des conditions -->
            <div class="space-y-8 mb-8">
                <!-- Acceptation des conditions -->
                <div class="border-l-4 border-indigo-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">1. Acceptation des conditions</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <p class="text-gray-600 mb-4">
                            L'accès et l'utilisation de GuinéeMall constituent votre acceptation sans réserve 
                            des présentes conditions. Ces conditions s'appliquent à tous les utilisateurs : 
                            clients, vendeurs et visiteurs.
                        </p>
                        <p class="text-gray-600">
                            Date d'entrée en vigueur : {{ $stats['platform_created'] ?? '2024-01-01' }}<br>
                            Dernière modification : {{ $stats['last_update'] ?? now()->format('d/m/Y') }}
                        </p>
                    </div>
                </div>

                <!-- Responsabilités des utilisateurs -->
                <div class="border-l-4 border-orange-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">2. Responsabilités des utilisateurs</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h4 class="font-semibold text-gray-900 mb-2">Pour les clients :</h4>
                        <ul class="list-disc list-inside text-gray-600 space-y-2 mb-4">
                            <li>Fournir des informations exactes et véridiques</li>
                            <li>Respecter les délais de paiement</li>
                            <li>Communiquer de manière respectueuse avec les vendeurs</li>
                            <li>Respecter les politiques de retour</li>
                        </ul>
                        
                        <h4 class="font-semibold text-gray-900 mb-2">Pour les vendeurs :</h4>
                        <ul class="list-disc list-inside text-gray-600 space-y-2">
                            <li>Vendre des produits conformes à la description</li>
                            <li>Maintenir des informations de boutique à jour</li>
                            <li>Traiter les commandes dans les délais convenus</li>
                            <li>Garantir la qualité des produits et services</li>
                        </ul>
                    </div>
                </div>

                <!-- Transactions et paiements -->
                <div class="border-l-4 border-pink-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">3. Transactions et paiements sécurisés</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <p class="text-gray-600 mb-4">
                            GuinéeMall garantit {{ $stats['secure_transactions'] ?? '100%' }} de transactions sécurisées 
                            grâce à nos partenaires de paiement certifiés. Tous les paiements sont protégés 
                            et les fonds sont conservés en sécurité jusqu'à la confirmation de réception.
                        </p>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="bg-white rounded-lg p-4">
                                <h5 class="font-semibold text-gray-900 mb-2">🔒 Sécurité</h5>
                                <p class="text-sm text-gray-600">Chiffrement SSL et protocoles de sécurité avancés</p>
                            </div>
                            <div class="bg-white rounded-lg p-4">
                                <h5 class="font-semibold text-gray-900 mb-2">🛡️ Protection</h5>
                                <p class="text-sm text-gray-600">Politique de remboursement et médiation des litiges</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Produits Interdits -->
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-times text-red-600"></i>
                        </span>
                        Produits Interdits
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                <span class="font-medium text-red-800">Produits illégaux</span>
                            </div>
                            <p class="text-red-700 text-sm">Contrefaçon, drogues, armes</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                <span class="font-medium text-red-800">Produits dangereux</span>
                            </div>
                            <p class="text-red-700 text-sm">Substances nocives, armes</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                <span class="font-medium text-red-800">Contenus violents</span>
                            </div>
                            <p class="text-red-700 text-sm">Violence, contenu inapproprié</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-times-circle text-red-600 mr-2"></i>
                                <span class="font-medium text-red-800">Droits d'auteur</span>
                            </div>
                            <p class="text-red-700 text-sm">Contrefaisant, piratage</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section Améliorée -->
        <section class="py-20 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 text-white relative overflow-hidden">
            <!-- Animated Background -->
            <div class="absolute inset-0">
                <div class="absolute top-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            </div>
            
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="mb-8">
                    <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full border border-white/30 mb-6">
                        <i class="fas fa-headset mr-2"></i>
                        <span class="text-sm font-semibold">Support disponible</span>
                    </div>
                </div>
                
                <h2 class="text-4xl md:text-5xl font-black mb-6">
                    Des questions ?
                </h2>
                <p class="text-xl text-white/90 mb-12 max-w-2xl mx-auto">
                    Notre équipe d'experts est là pour vous aider à comprendre nos conditions et à vous accompagner
                </p>
                
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="{{ route('pages.contact') }}" class="group inline-flex items-center px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-xl">
                        <i class="fas fa-envelope mr-3 group-hover:animate-bounce"></i>
                        <span>Nous contacter</span>
                        <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="{{ route('pages.faq') }}" class="group inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-bold hover:bg-white hover:text-indigo-600 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-question-circle mr-3 group-hover:animate-pulse"></i>
                        <span>Voir la FAQ</span>
                        <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-8 mt-16">
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">24/7</div>
                        <div class="text-white/80 text-sm">Support disponible</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">&lt; 2h</div>
                        <div class="text-white/80 text-sm">Temps de réponse</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">98%</div>
                        <div class="text-white/80 text-sm">Satisfaction client</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection
