@extends('layouts.public')

@section('title', 'Support 24/7 - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Support 24/7</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="primary-yellow p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-headset text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Support 24/7</h1>
            <p class="text-yellow-100 text-lg">Assistance disponible à tout moment</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Introduction -->
            <div class="mb-12">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Notre équipe de support est là pour vous aider 24h/24 et 7j/7. 
                    Que vous ayez une question sur une commande, besoin d'aide technique ou 
                    simplement envie d'en savoir plus sur nos services, nous sommes là pour vous.
                </p>
            </div>

            <!-- Support Channels -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-phone text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Téléphone</h3>
                        <p class="text-gray-600">+224 622 123 456</p>
                        <p class="text-sm text-gray-500">Disponible 24/7</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-envelope text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Email</h3>
                        <p class="text-gray-600">support@guineemall.com</p>
                        <p class="text-sm text-gray-500">Réponse sous 2h</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-comments text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Chat en direct</h3>
                        <p class="text-gray-600">Disponible sur le site</p>
                        <p class="text-sm text-gray-500">Réponse immédiate</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fab fa-whatsapp text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">WhatsApp</h3>
                        <p class="text-gray-600">+224 622 123 456</p>
                        <p class="text-sm text-gray-500">Support rapide</p>
                    </div>
                </div>
            </div>

            <!-- Support Hours -->
            <div class="bg-gray-50 rounded-xl p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Horaires de support</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-600 mb-2">Support prioritaire</h4>
                        <p class="text-sm text-gray-600">24h/24, 7j/7</p>
                    </div>
                    <div class="bg-white rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-600 mb-2">Support standard</h4>
                        <p class="text-sm text-gray-600">Lun-Ven: 8h-20h, Sam-Dim: 9h-18h</p>
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
