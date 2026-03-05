@extends('layouts.public')

@section('title', 'Politique de Confidentialité - GuinéeMall')

@section('content')
<!-- Hero Section Amélioré -->
<section class="relative bg-gradient-to-br from-yellow-600 via-orange-600 to-yellow-700 text-white py-20 overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0">
        <div class="absolute top-0 left-0 w-96 h-96 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s;"></div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center">
            <!-- Badge -->
            <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full border border-white/30 mb-6">
                <i class="fas fa-shield-alt mr-2"></i>
                <span class="text-sm font-semibold text-white uppercase tracking-wider">Protection des données</span>
            </div>
            
            <h1 class="text-5xl md:text-6xl font-black mb-6 leading-tight">
                Politique de Confidentialité
            </h1>
            <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-3xl mx-auto leading-relaxed">
                Notre engagement pour la protection de vos données personnelles
            </p>
            
            <!-- Navigation rapide -->
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <a href="#donnees" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-lg text-white hover:bg-white/30 transition-all duration-300 border border-white/30">
                    <i class="fas fa-database mr-2"></i>Données collectées
                </a>
                <a href="#utilisation" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-lg text-white hover:bg-white/30 transition-all duration-300 border border-white/30">
                    <i class="fas fa-cogs mr-2"></i>Utilisation
                </a>
                <a href="#droits" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-lg text-white hover:bg-white/30 transition-all duration-300 border border-white/30">
                    <i class="fas fa-user-shield mr-2"></i>Vos droits
                </a>
            </div>
            
            <a href="{{ route('home') }}" class="inline-flex items-center px-8 py-4 bg-white text-yellow-600 rounded-xl font-bold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-xl">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</section>

<!-- Privacy Content -->
<section class="py-16 bg-gradient-to-br from-gray-50 to-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Last Updated -->
        <div class="mb-12 p-6 bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-2xl shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-calendar-alt text-yellow-600 text-2xl mr-3"></i>
                    <div>
                        <p class="text-yellow-800 font-semibold">Dernière mise à jour</p>
                        <p class="text-yellow-600 text-sm">{{ date('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i>RGPD Compliant
                    </span>
                    <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">
                        <i class="fas fa-lock mr-1"></i>Sécurisé
                    </span>
                </div>
            </div>
        </div>

        <!-- Introduction -->
        <div class="mb-16">
            <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">1. Introduction</h2>
                </div>
                <p class="text-gray-700 text-lg leading-relaxed mb-6">
                    Chez GuinéeMall, nous prenons la protection de vos données personnelles très au sérieux. 
                    Cette politique explique comment nous collectons, utilisons et protégeons vos informations.
                </p>
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 p-6 rounded-xl border border-yellow-200">
                    <p class="text-yellow-800 font-medium mb-3">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Notre engagement :</strong>
                    </p>
                    <p class="text-yellow-700">
                        Nous nous engageons à traiter vos données avec le plus grand soin et en conformité 
                        avec la législation en vigueur.
                    </p>
                </div>
            </div>
        </div>

        <!-- Données Collectées -->
        <div id="donnees" class="mb-16">
            <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-database text-white text-2xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">2. Données que nous Collectons</h2>
                </div>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-yellow-600"></i>
                            </span>
                            Informations Personnelles
                        </h3>
                        <div class="space-y-3">
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-id-card text-yellow-600 mr-2"></i>
                                    <span class="font-medium text-yellow-800">Identité</span>
                                </div>
                                <p class="text-yellow-700 text-sm">Nom, prénom, email, téléphone</p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-map-marker-alt text-yellow-600 mr-2"></i>
                                    <span class="font-medium text-yellow-800">Localisation</span>
                                </div>
                                <p class="text-yellow-700 text-sm">Adresse, coordonnées géographiques</p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-credit-card text-yellow-600 mr-2"></i>
                                    <span class="font-medium text-yellow-800">Paiement</span>
                                </div>
                                <p class="text-yellow-700 text-sm">Informations de paiement sécurisées</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-laptop text-orange-600"></i>
                            </span>
                            Données Techniques
                        </h3>
                        <div class="space-y-3">
                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-globe text-orange-600 mr-2"></i>
                                    <span class="font-medium text-orange-800">Navigation</span>
                                </div>
                                <p class="text-orange-700 text-sm">IP, navigateur, appareil utilisé</p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-chart-line text-orange-600 mr-2"></i>
                                    <span class="font-medium text-orange-800">Utilisation</span>
                                </div>
                                <p class="text-orange-700 text-sm">Pages visitées, temps passé</p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-cookie text-orange-600 mr-2"></i>
                                    <span class="font-medium text-orange-800">Cookies</span>
                                </div>
                                <p class="text-orange-700 text-sm">Technologies similaires</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section Améliorée -->
        <section class="py-20 bg-gradient-to-br from-yellow-600 via-orange-600 to-yellow-700 text-white relative overflow-hidden">
            <!-- Animated Background -->
            <div class="absolute inset-0">
                <div class="absolute top-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            </div>
            
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="mb-8">
                    <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full border border-white/30 mb-6">
                        <i class="fas fa-headset mr-2"></i>
                        <span class="text-sm font-semibold">Support confidentialité</span>
                    </div>
                </div>
                
                <h2 class="text-4xl md:text-5xl font-black mb-6">
                    Questions sur vos données ?
                </h2>
                <p class="text-xl text-white/90 mb-12 max-w-2xl mx-auto">
                    Notre équipe DPD est disponible pour répondre à toutes vos questions
                </p>
                
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="{{ route('pages.contact') }}" class="group inline-flex items-center px-8 py-4 bg-white text-yellow-600 rounded-xl font-bold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-xl">
                        <i class="fas fa-envelope mr-3 group-hover:animate-bounce"></i>
                        <span>Nous contacter</span>
                        <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="{{ route('pages.legal') }}" class="group inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-bold hover:bg-white hover:text-yellow-600 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-balance-scale mr-3 group-hover:animate-pulse"></i>
                        <span>Mentions légales</span>
                        <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-8 mt-16">
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">100%</div>
                        <div class="text-white/80 text-sm">Conformité RGPD</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">256-bit</div>
                        <div class="text-white/80 text-sm">Chiffrement SSL</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">24/7</div>
                        <div class="text-white/80 text-sm">Support DPD</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection
