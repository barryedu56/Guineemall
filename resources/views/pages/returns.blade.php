@extends('layouts.public')

@section('title', 'Politique de Retour - GuinéeMall')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-yellow-600">Accueil</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li class="text-gray-900 font-medium">Politique de Retour</li>
        </ol>
    </nav>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="primary-yellow p-8 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-undo text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-4">Politique de Retour</h1>
            <p class="text-yellow-100 text-lg">Satisfait ou remboursé</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Introduction -->
            <div class="mb-12">
                <p class="text-gray-600 text-lg leading-relaxed mb-6">
                    Votre satisfaction est notre priorité. Si vous n'êtes pas complètement satisfait 
                    de votre achat, nous vous offrons une politique de retour simple et flexible 
                    pour vous rembourser ou échanger votre produit.
                </p>
            </div>

            <!-- Return Policy Features -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-calendar-alt text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">30 jours de retour</h3>
                        <p class="text-gray-600">Retour possible sous 30 jours après réception.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-sync-alt text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Échange gratuit</h3>
                        <p class="text-gray-600">Échangez votre produit contre un autre.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-money-bill-wave text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Remboursement complet</h3>
                        <p class="text-gray-600">Remboursement intégral du prix d'achat.</p>
                    </div>
                </div>

                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-truck text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Retour gratuit</h3>
                        <p class="text-gray-600">Frais de retour pris en charge.</p>
                    </div>
                </div>
            </div>

            <!-- Return Process -->
            <div class="bg-gray-50 rounded-xl p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6">Comment retourner un article ?</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg p-4 text-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-file-alt text-yellow-600 text-xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-900 mb-2">1. Demande</h4>
                        <p class="text-sm text-gray-600">Faites votre demande en ligne</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-box text-yellow-600 text-xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-900 mb-2">2. Expédition</h4>
                        <p class="text-sm text-gray-600">Envoyez l'article gratuitement</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 text-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-check text-yellow-600 text-xl"></i>
                        </div>
                        <h4 class="font-semibold text-gray-900 mb-2">3. Remboursement</h4>
                        <p class="text-sm text-gray-600">Recevez votre remboursement</p>
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
