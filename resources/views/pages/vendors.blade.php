@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-yellow-500 to-orange-600 text-white py-20">
    <div class="absolute inset-0 bg-black opacity-20"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">Devenir Vendeur sur GuinéeMall</h1>
            <p class="text-xl md:text-2xl mb-8 text-yellow-100">
                Rejoignez la marketplace N°1 en Guinée et développez votre business
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="bg-white text-yellow-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                    <i class="fas fa-rocket mr-2"></i>
                    Commencer gratuitement
                </a>
                <a href="{{ route('home') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-yellow-600 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Why Sell on GuinéeMall -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Pourquoi vendre sur GuinéeMall ?</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                La plateforme qui vous donne accès à des milliers de clients en Guinée
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Large Audience -->
            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-users text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Large Audience</h3>
                <p class="text-gray-600">Accédez à des milliers de clients potentiels à travers toute la Guinée</p>
            </div>

            <!-- Low Fees -->
            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-percentage text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Frais Réduits</h3>
                <p class="text-gray-600">Des commissions compétitives pour maximiser vos bénéfices</p>
            </div>

            <!-- Easy Management -->
            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-cog text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Gestion Simple</h3>
                <p class="text-gray-600">Interface intuitive pour gérer facilement vos produits et commandes</p>
            </div>

            <!-- Secure Payments -->
            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-shield-alt text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Paiements Sécurisés</h3>
                <p class="text-gray-600">Transactions protégées et paiements rapides garantis</p>
            </div>

            <!-- Analytics -->
            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Analytics Avancés</h3>
                <p class="text-gray-600">Suivez vos performances avec des statistiques détaillées</p>
            </div>

            <!-- Support -->
            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-headset text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Support Dédié</h3>
                <p class="text-gray-600">Assistance personnalisée pour vous aider à réussir</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Comment ça marche ?</h2>
            <p class="text-lg text-gray-600">Devenez vendeur en 3 étapes simples</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Step 1 -->
            <div class="text-center">
                <div class="w-16 h-16 bg-yellow-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                    1
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Inscrivez-vous</h3>
                <p class="text-gray-600">Créez votre compte vendeur en quelques minutes</p>
            </div>

            <!-- Step 2 -->
            <div class="text-center">
                <div class="w-16 h-16 bg-yellow-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                    2
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Ajoutez vos produits</h3>
                <p class="text-gray-600">Mettez en ligne vos articles avec photos et descriptions</p>
            </div>

            <!-- Step 3 -->
            <div class="text-center">
                <div class="w-16 h-16 bg-yellow-600 text-white rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                    3
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Vendez et gagnez</h3>
                <p class="text-gray-600">Recevez des commandes et développez votre business</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-r from-yellow-600 to-orange-600 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Prêt à commencer ?</h2>
        <p class="text-xl mb-8 text-yellow-100">
            Rejoignez des milliers de vendeurs qui font confiance à GuinéeMall
        </p>
        <a href="{{ route('register') }}" class="bg-white text-yellow-600 px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-colors inline-block">
            <i class="fas fa-rocket mr-2"></i>
            Commencer à vendre maintenant
        </a>
    </div>
</section>
@endsection
