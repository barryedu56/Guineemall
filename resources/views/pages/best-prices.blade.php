@extends('layouts.public')

@section('title', 'Meilleurs Prix Garantis - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Meilleurs Prix Garantis</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="primary-yellow p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-tag text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Meilleurs Prix Garantis</h1>
            <p class="text-yellow-100 text-lg">Comparez et économisez sur GuinéeMall</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Introduction -->
            <div class="mb-12">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Chez GuinéeMall, nous nous engageons à vous offrir les meilleurs prix du marché. 
                    Comparez facilement les prix de différents vendeurs et bénéficiez d'offres 
                    exclusives que vous ne trouverez nulle part ailleurs.
                </p>
            </div>

            <!-- Price Guarantee Features -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Comparaison de prix</h3>
                        <p class="text-gray-600">Comparez les prix de plusieurs vendeurs pour le même produit.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-percentage text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Promotions exclusives</h3>
                        <p class="text-gray-600">Profitez de réductions uniques sur GuinéeMall.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-gift text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Offres spéciales</h3>
                        <p class="text-gray-600">Découvrez des offres groupées et packs avantageux.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-coins text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Prix négociés</h3>
                        <p class="text-gray-600">Nos vendeurs proposent des prix compétitifs.</p>
                    </div>
                </div>
            </div>

            <!-- Price Comparison Example -->
            <div class="bg-gray-50 rounded-xl p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Exemple de comparaison de prix</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 text-center">
                        <h4 class="font-semibold text-gray-900 mb-2">Smartphone X</h4>
                        <p class="text-2xl font-bold text-yellow-600 mb-2">450 000 GNF</p>
                        <p class="text-sm text-gray-600">GuinéeMall</p>
                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Meilleur prix</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <h4 class="font-semibold text-gray-900 mb-2">Smartphone X</h4>
                        <p class="text-2xl font-bold text-gray-600 mb-2">520 000 GNF</p>
                        <p class="text-sm text-gray-600">Magasin A</p>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">+15%</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <h4 class="font-semibold text-gray-900 mb-2">Smartphone X</h4>
                        <p class="text-2xl font-bold text-gray-600 mb-2">580 000 GNF</p>
                        <p class="text-sm text-gray-600">Magasin B</p>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">+29%</span>
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
