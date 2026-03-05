@extends('layouts.public')

@section('title', 'Politique des Cookies - GuinéeMall')

@section('content')
<!-- Hero Section Amélioré -->
<section class="relative bg-gradient-to-br from-orange-600 via-amber-600 to-yellow-600 text-white py-20 overflow-hidden">
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
                <i class="fas fa-cookie-bite mr-2"></i>
                <span class="text-sm font-semibold text-white uppercase tracking-wider">Technologies web</span>
            </div>
            
            <h1 class="text-5xl md:text-6xl font-black mb-6 leading-tight">
                Politique des Cookies
            </h1>
            <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-3xl mx-auto leading-relaxed">
                Comment nous utilisons les cookies pour améliorer votre expérience
            </p>
            
            <!-- Navigation rapide -->
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <a href="#types" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-lg text-white hover:bg-white/30 transition-all duration-300 border border-white/30">
                    <i class="fas fa-list mr-2"></i>Types de cookies
                </a>
                <a href="#gestion" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-lg text-white hover:bg-white/30 transition-all duration-300 border border-white/30">
                    <i class="fas fa-cog mr-2"></i>Gestion
                </a>
                <a href="#preferences" class="px-6 py-3 bg-white/20 backdrop-blur-sm rounded-lg text-white hover:bg-white/30 transition-all duration-300 border border-white/30">
                    <i class="fas fa-sliders-h mr-2"></i>Préférences
                </a>
            </div>
            
            <a href="{{ route('home') }}" class="inline-flex items-center px-8 py-4 bg-white text-orange-600 rounded-xl font-bold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-xl">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>
</section>

<!-- Cookies Content -->
<section class="py-16 bg-gradient-to-br from-gray-50 to-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Last Updated -->
        <div class="mb-12 p-6 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-2xl shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-calendar-alt text-orange-600 text-2xl mr-3"></i>
                    <div>
                        <p class="text-orange-800 font-semibold">Dernière mise à jour</p>
                        <p class="text-orange-600 text-sm">{{ date('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                        <i class="fas fa-check-circle mr-1"></i>ePrivacy Directive
                    </span>
                    <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium">
                        <i class="fas fa-cookie-bite mr-1"></i>GDPR Ready
                    </span>
                </div>
            </div>
        </div>

        <!-- Introduction -->
        <div class="mb-16">
            <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-cookie-bite text-white text-2xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">1. Qu'est-ce qu'un Cookie ?</h2>
                </div>
                <p class="text-gray-700 text-lg leading-relaxed mb-6">
                    Un cookie est un petit fichier texte stocké sur votre appareil lorsque vous visitez notre site. 
                    Il nous aide à vous offrir une meilleure expérience de navigation.
                </p>
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 p-6 rounded-xl border border-orange-200">
                    <p class="text-orange-800 font-medium mb-3">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Pourquoi les cookies ?</strong>
                    </p>
                    <p class="text-orange-700">
                        Les cookies sont essentiels au fonctionnement de la plupart des sites web modernes 
                        et aident à améliorer votre expérience de navigation.
                    </p>
                </div>
            </div>
        </div>

        <!-- Types de Cookies -->
        <div id="types" class="mb-16">
            <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-list text-white text-2xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">2. Types de Cookies</h2>
                </div>
                
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-check text-yellow-600"></i>
                            </span>
                            Cookies Essentiels
                        </h3>
                        <div class="space-y-3">
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-shield-alt text-yellow-600 mr-2"></i>
                                    <span class="font-medium text-yellow-800">Sécurité</span>
                                </div>
                                <p class="text-yellow-700 text-sm">Authentification et protection CSRF</p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-shopping-cart text-yellow-600 mr-2"></i>
                                    <span class="font-medium text-yellow-800">Panier</span>
                                </div>
                                <p class="text-yellow-700 text-sm">Sauvegarde des articles</p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-palette text-yellow-600 mr-2"></i>
                                    <span class="font-medium text-yellow-800">Préférences</span>
                                </div>
                                <p class="text-yellow-700 text-sm">Langue et paramètres</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <span class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-chart-line text-blue-600"></i>
                            </span>
                            Cookies Performance
                        </h3>
                        <div class="space-y-3">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                                    <span class="font-medium text-blue-800">Analytics</span>
                                </div>
                                <p class="text-blue-700 text-sm">Pages visitées, temps passé</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>
                                    <span class="font-medium text-blue-800">Optimisation</span>
                                </div>
                                <p class="text-blue-700 text-sm">Tests A/B, améliorations</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-bug text-blue-600 mr-2"></i>
                                    <span class="font-medium text-blue-800">Debug</span>
                                </div>
                                <p class="text-blue-700 text-sm">Détection d'erreurs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section Améliorée -->
        <section class="py-20 bg-gradient-to-br from-orange-600 via-amber-600 to-yellow-600 text-white relative overflow-hidden">
            <!-- Animated Background -->
            <div class="absolute inset-0">
                <div class="absolute top-0 left-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            </div>
            
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="mb-8">
                    <div class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full border border-white/30 mb-6">
                        <i class="fas fa-cog mr-2"></i>
                        <span class="text-sm font-semibold">Gestion des préférences</span>
                    </div>
                </div>
                
                <h2 class="text-4xl md:text-5xl font-black mb-6">
                    Gérez vos préférences
                </h2>
                <p class="text-xl text-white/90 mb-12 max-w-2xl mx-auto">
                    Contrôlez les cookies et personnalisez votre expérience
                </p>
                
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="{{ route('pages.contact') }}" class="group inline-flex items-center px-8 py-4 bg-white text-orange-600 rounded-xl font-bold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 shadow-xl">
                        <i class="fas fa-envelope mr-3 group-hover:animate-bounce"></i>
                        <span>Nous contacter</span>
                        <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="{{ route('pages.privacy') }}" class="group inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-bold hover:bg-white hover:text-orange-600 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-shield-alt mr-3 group-hover:animate-pulse"></i>
                        <span>Confidentialité</span>
                        <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-8 mt-16">
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">0%</div>
                        <div class="text-white/80 text-sm">Cookies non essentiels</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">100%</div>
                        <div class="text-white/80 text-sm">Conformité GDPR</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold mb-2">24/7</div>
                        <div class="text-white/80 text-sm">Support disponible</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection
