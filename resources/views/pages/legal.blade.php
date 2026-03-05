@extends('layouts.public')

@section('title', 'Mentions Légales - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Mentions Légales</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-br from-gray-800 via-gray-900 to-black p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-balance-scale text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Mentions Légales</h1>
            <p class="text-gray-300 text-lg">Informations légales sur la société GuinéeMall</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Introduction -->
            <div class="mb-12">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Conformément aux dispositions de la loi n°°°° du °°°° relative à la confiance 
                    dans l'économie numérique, nous vous informons de l'identité des différents intervenants 
                    dans le cadre de la réalisation et du suivi du site GuinéeMall.
                </p>
            </div>

            <!-- Legal Information -->
            <div class="space-y-8 mb-12">
                <!-- Éditeur du site -->
                <div class="border-l-4 border-yellow-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Éditeur du site</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Raison sociale</p>
                                <p class="font-medium text-gray-900">GuinéeMall SARL</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Forme juridique</p>
                                <p class="font-medium text-gray-900">Société à Responsabilité Limitée</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Capital social</p>
                                <p class="font-medium text-gray-900">10 000 000 GNF</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">N° RCCM</p>
                                <p class="font-medium text-gray-900">RCCM-CONAKRY-2024-B-12345</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">N° Contribuable</p>
                                <p class="font-medium text-gray-900">NIF: 1234567890123</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Siège social</p>
                                <p class="font-medium text-gray-900">Conakry, Guinée</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Directeur de la publication -->
                <div class="border-l-4 border-blue-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Directeur de la publication</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Nom</p>
                                <p class="font-medium text-gray-900">Directeur Général GuinéeMall</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Email</p>
                                <p class="font-medium text-gray-900">legal@guineemall.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hébergeur -->
                <div class="border-l-4 border-orange-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Hébergeur du site</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Société</p>
                                <p class="font-medium text-gray-900">Cloud Services Provider</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Adresse</p>
                                <p class="font-medium text-gray-900">Data Center, Conakry</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Propriété intellectuelle -->
                <div class="border-l-4 border-orange-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Propriété intellectuelle</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <p class="text-gray-600 mb-4">
                            L'ensemble de ce site relève de la législation française et internationale sur le droit d'auteur 
                            et la propriété intellectuelle. Tous les droits de reproduction sont réservés.
                        </p>
                        <p class="text-gray-600">
                            La reproduction totale ou partielle de ce site sur quelque support que ce soit, 
                            est formellement interdite sauf autorisation expresse du directeur de la publication.
                        </p>
                    </div>
                </div>

                <!-- Données personnelles -->
                <div class="border-l-4 border-red-500 pl-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Protection des données personnelles</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <p class="text-gray-600 mb-4">
                            Conformément à la loi Informatique et Libertés, vous disposez d'un droit d'accès, 
                            de modification et de suppression des données vous concernant.
                        </p>
                        <p class="text-gray-600">
                            Pour exercer ce droit, adressez-vous à : <strong>privacy@guineemall.com</strong>
                        </p>
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
