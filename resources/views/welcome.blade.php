<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app_name('public', 'title') }} - La marketplace N°1 en Guinée</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<style>
    body { font-family: 'Inter', sans-serif; }
    
    /* Modern E-commerce Theme */
    .primary-yellow { background: linear-gradient(135deg, #FFC107 0%, #FFA726 100%); }
    .accent-orange { background: linear-gradient(135deg, #FFA726 0%, #FF9800 100%); }
    .gradient-secondary { background: linear-gradient(135deg, #FFE082 0%, #FFCC80 100%); }
    .gradient-warm { background: linear-gradient(135deg, #FFB300 0%, #FF8F00 100%); }
    
    /* Professional Cards */
    .marketplace-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.08);
    }
    .marketplace-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(255, 193, 7, 0.15);
        border-color: rgba(255, 193, 7, 0.2);
    }
    
    /* Product Cards */
    .product-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.06);
    }
    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
    .product-image {
        aspect-ratio: 1;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    .product-card:hover .product-image {
        transform: scale(1.05);
    }
    
    /* Trust Badges */
    .trust-badge {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.2);
        transition: all 0.3s ease;
    }
    .trust-badge:hover {
        background: rgba(255, 193, 7, 0.15);
        transform: translateY(-2px);
    }
    
    /* CTA Buttons */
    .cta-primary {
        background: linear-gradient(135deg, #FFC107 0%, #FFA726 100%);
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    }
    .cta-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 193, 7, 0.4);
    }
    .cta-secondary {
        border: 2px solid #FFC107;
        transition: all 0.3s ease;
    }
    .cta-secondary:hover {
        background: rgba(255, 193, 7, 0.05);
        transform: translateY(-2px);
    }
    
    /* Category Cards */
    .category-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.08);
        cursor: pointer;
    }
    .category-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        border-color: rgba(255, 193, 7, 0.3);
    }
    
    /* Mobile Optimizations */
    @media (max-width: 768px) {
        .hero-title { font-size: 2.5rem; line-height: 1.2; }
        .hero-subtitle { font-size: 1.1rem; }
        .mobile-stack { flex-direction: column !important; }
        .mobile-center { text-align: center !important; }
    }
</style>

<body class="bg-gray-50">
    <!-- Enhanced Navigation Header -->
    <nav class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900">{!! app_name('public', 'logo') !!}</span>
                    </a>
                </div>

                <!-- Search Bar (Desktop) -->
                <div class="hidden lg:flex flex-1 max-w-2xl mx-8">
                    <form action="{{ route('client.catalog.index') }}" method="GET" class="relative w-full">
                        <input type="text" 
                               name="search"
                               placeholder="Rechercher {{ $totalProducts }}+ produits..." 
                               class="w-full px-4 py-2.5 pl-12 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-yellow-500 focus:bg-white focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-20 transition-all">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-lg hover:opacity-90 transition-all">
                            <i class="fas fa-search text-sm"></i>
                        </button>
                    </form>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-6">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-yellow-600 font-medium transition-colors">
                            <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                        </a>
                        
                        <!-- Messages et Notifications -->
                        <div class="flex items-center space-x-3">
                            <!-- Notifications de chat -->
                            @livewire('chat-notification')
                            
                            <!-- Messages -->
                            <a href="{{ route('chat.index') }}" 
                               class="text-gray-700 hover:text-yellow-600 font-medium transition-colors">
                                <i class="fas fa-comments mr-2"></i>Messages
                            </a>
                        </div>
                    @endauth
                    <a href="{{ route('client.catalog.index') }}" class="text-gray-700 hover:text-yellow-600 font-medium transition-colors">
                        <i class="fas fa-shopping-bag mr-2"></i>Produits
                    </a>
                    @guest
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-yellow-600 font-medium transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                        </a>
                        <a href="{{ route('register') }}" class="cta-primary text-white px-4 py-2 rounded-xl font-medium">
                            <i class="fas fa-user-plus mr-2"></i>Inscription
                        </a>
                    @endguest
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-yellow-600 p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Search -->
        <div id="mobileSearch" class="hidden md:hidden px-4 pb-4">
            <form action="{{ route('client.catalog.index') }}" method="GET">
                <div class="relative">
                    <input type="text" 
                           name="search"
                           placeholder="Rechercher des produits..." 
                           class="w-full px-4 py-2.5 pl-12 pr-4 text-gray-700 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-yellow-500 focus:bg-white">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-lg">
                        <i class="fas fa-search text-sm"></i>
                    </button>
                </div>
            </form>
            
            @auth
            <!-- Liens mobiles pour les utilisateurs connectés -->
            <div class="mt-4 space-y-2">
                <a href="{{ route('chat.index') }}" 
                   class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-comments mr-2"></i>Messages
                </a>
                <a href="{{ url('/dashboard') }}" 
                   class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                </a>
            </div>
            @endauth
        </div>
    </nav>

    <!-- Enhanced Hero Section - Marketplace Africaine Moderne -->
    <section class="relative overflow-hidden" style="background-image: url('{{ asset('img/R.jpeg') }}'); background-size: cover; background-position: center;">
        <!-- Background Overlay Premium -->
        <div class="absolute inset-0 bg-gradient-to-br from-yellow-900/70 via-orange-800/60 to-yellow-900/70"></div>
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-20 left-10 w-32 h-32 bg-yellow-400/20 rounded-full animate-pulse"></div>
            <div class="absolute top-1/3 right-20 w-48 h-48 bg-white/10 rounded-full animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute bottom-20 left-1/4 w-40 h-40 bg-yellow-300/15 rounded-full animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20 lg:py-32">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Hero Content Premium -->
                <div class="text-center lg:text-left">
                    <!-- Live Badge Premium -->
                    <div class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm rounded-full mb-8 border border-white/30">
                        <span class="flex h-3 w-3 bg-yellow-400 rounded-full mr-3 animate-pulse"></span>
                        <span class="text-sm font-bold text-white">{{ $totalProducts }}+ produits disponibles maintenant</span>
                    </div>
                    
                    <!-- Main Headline Premium -->
                    <h1 class="text-5xl lg:text-7xl font-black text-white mb-6 leading-tight">
                        La marketplace
                        <span class="text-yellow-300">N°1 en Guinée</span>
                    </h1>

                    <!-- Subtitle Premium -->
                    <p class="text-xl lg:text-2xl text-white/90 mb-10 max-w-2xl leading-relaxed">
                        Connectez-vous avec <span class="font-bold text-yellow-300">{{ $totalVendors }}+ vendeurs</span> 
                        et découvrez des milliers de produits authentiquement guinéens
                    </p>

                    <!-- CTA Buttons Premium -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="{{ route('client.catalog.index') }}" class="group relative px-8 py-4 bg-yellow-400 text-gray-900 rounded-full font-bold text-lg hover:bg-yellow-300 transition-all transform hover:scale-105 shadow-2xl">
                            <span class="relative z-10 flex items-center">
                                <i class="fas fa-shopping-bag mr-3"></i>
                                Découvrir les produits
                                <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                            </span>
                            <div class="absolute inset-0 bg-gradient-to-r from-yellow-300 to-yellow-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </a>
                        <a href="{{ route('register') }}" class="px-8 py-4 bg-white/20 backdrop-blur-sm text-white rounded-full font-bold text-lg hover:bg-white/30 transition-all border border-white/30">
                            <i class="fas fa-store mr-2"></i>
                            Devenir vendeur
                        </a>
                    </div>
                </div>

                <!-- Featured Product Showcase Premium -->
                <div class="relative">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl shadow-2xl p-6 border border-white/20">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <span class="mr-2">🔥</span>
                                Produits populaires
                            </h3>
                            <a href="{{ route('client.catalog.index') }}" class="text-sm text-yellow-300 hover:text-yellow-200 font-medium flex items-center">
                                Voir tout <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            @forelse($featuredProducts as $product)
                                <a href="{{ route('client.catalog.show', $product) }}" class="product-card bg-white/10 backdrop-blur-sm rounded-xl p-3 border border-white/20 hover:bg-white/20 transition-all">
                                    <div class="aspect-square rounded-lg overflow-hidden mb-3 bg-white/20">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" 
                                                 alt="{{ $product->name }}" 
                                                 class="product-image w-full h-full">
                                        @else
                                            <div class="w-full h-full bg-white/10 flex items-center justify-center">
                                                <i class="fas fa-image text-white/50 text-2xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-semibold text-white truncate mb-1">{{ $product->name }}</h4>
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-bold text-yellow-300">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                                        @if($product->vendor)
                                            <div class="text-xs text-white/70 flex items-center">
                                                <i class="fas fa-store mr-1"></i>
                                                {{ Str::limit($product->vendor->shop_name, 8) }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <!-- Demo Products Premium -->
                                @foreach([
                                    ['name' => 'iPhone 15 Pro', 'price' => 1500000, 'vendor' => 'TechStore', 'icon' => '📱'],
                                    ['name' => 'Robe Africaine', 'price' => 75000, 'vendor' => 'ModeGuinée', 'icon' => '👗'],
                                    ['name' => 'Ordinateur Portable', 'price' => 2500000, 'vendor' => 'ComputerPlus', 'icon' => '💻'],
                                    ['name' => 'Montre Connectée', 'price' => 350000, 'vendor' => 'WatchShop', 'icon' => '⌚'],
                                ] as $demoProduct)
                                    <div class="product-card bg-white/10 backdrop-blur-sm rounded-xl p-3 border border-white/20">
                                        <div class="aspect-square rounded-lg overflow-hidden mb-3 bg-white/20 flex items-center justify-center">
                                            <span class="text-4xl">{{ $demoProduct['icon'] }}</span>
                                        </div>
                                        <h4 class="text-sm font-semibold text-white truncate mb-1">{{ $demoProduct['name'] }}</h4>
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-bold text-yellow-300">{{ number_format($demoProduct['price'], 0, ',', ' ') }} GNF</p>
                                            <div class="text-xs text-white/70">{{ $demoProduct['vendor'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose {{ app_name() }} Section - Premium -->
    <section class="py-20 bg-gradient-to-br from-gray-50 to-yellow-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-6">Pourquoi choisir {{ app_name() }} ?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    La marketplace de confiance qui simplifie vos achats et ventes en Guinée
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Payment Security Premium -->
                <a href="{{ route('pages.secure-payments') }}" class="group bg-white rounded-2xl p-8 text-center block shadow-lg hover:shadow-xl transition-all transform hover:scale-105 border border-gray-100">
                    <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shield-alt text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Paiements sécurisés</h3>
                    <p class="text-gray-600">Transactions protégées et remboursées en cas de problème</p>
                </a>

                <!-- Fast Delivery Premium -->
                <a href="{{ route('pages.delivery-service') }}" class="group bg-white rounded-2xl p-8 text-center block shadow-lg hover:shadow-xl transition-all transform hover:scale-105 border border-gray-100">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-cyan-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-truck text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Livraison rapide</h3>
                    <p class="text-gray-600">Réception de vos commandes en 24-48h à Conakry et principales villes</p>
                </a>

                <!-- 24/7 Support Premium -->
                <a href="{{ route('pages.support') }}" class="group bg-white rounded-2xl p-8 text-center block shadow-lg hover:shadow-xl transition-all transform hover:scale-105 border border-gray-100">
                    <div class="w-20 h-20 bg-gradient-to-br from-orange-400 to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-headset text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Support 24/7</h3>
                    <p class="text-gray-600">Assistance disponible par téléphone, email et chat en direct</p>
                </a>

                <!-- Best Prices Premium -->
                <a href="{{ route('pages.best-prices') }}" class="group bg-white rounded-2xl p-8 text-center block shadow-lg hover:shadow-xl transition-all transform hover:scale-105 border border-gray-100">
                    <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-tag text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Meilleurs prix</h3>
                    <p class="text-gray-600">Comparez les prix et bénéficiez des meilleures offres du marché</p>
                </a>

                <!-- Verified Vendors Premium -->
                <a href="{{ route('pages.verified-vendors') }}" class="group bg-white rounded-2xl p-8 text-center block shadow-lg hover:shadow-xl transition-all transform hover:scale-105 border border-gray-100">
                    <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-check-circle text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Vendeurs vérifiés</h3>
                    <p class="text-gray-600">Tous nos vendeurs sont authentifiés et notés par la communauté</p>
                </a>

                <!-- Easy Returns Premium -->
                <a href="{{ route('pages.returns') }}" class="group bg-white rounded-2xl p-8 text-center block shadow-lg hover:shadow-xl transition-all transform hover:scale-105 border border-gray-100">
                    <div class="w-20 h-20 bg-gradient-to-br from-orange-400 to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-undo text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Retours faciles</h3>
                    <p class="text-gray-600">Politique de retour simple et satisfait ou remboursé</p>
                </a>
            </div>
        </div>
    </section>

    <!-- NOUVELLE SECTION VISUELLE ÉMOTIONNELLE - ACHETEZ LOCAL -->
    <section class="py-20 bg-gradient-to-br from-yellow-50 to-orange-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                <!-- COLONNE GAUCHE - CONTENU TEXTE -->
                <div class="flex flex-col justify-center space-y-6">
                    <!-- Badge -->
                    <div class="inline-flex items-center px-4 py-2 bg-orange-100 rounded-full w-fit">
                        <span class="text-orange-600 font-bold text-sm">❤️ Soutenez l'économie locale</span>
                    </div>
                    
                    <!-- Titre Principal -->
                    <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">
                        Achetez local.<br>
                        <span class="text-orange-500">Soutenez les vendeurs guinéens.</span>
                    </h2>
                    
                    <!-- Texte Descriptif -->
                    <p class="text-xl text-gray-600 leading-relaxed">
                        Chaque achat sur {{ app_name() }} contribue au développement de l'économie locale 
                        et aide des milliers d'entrepreneurs guinéens à prospérer.
                    </p>
                    
                    <!-- Boutons CTA -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('client.catalog.index') }}" class="group px-8 py-4 bg-orange-500 text-white rounded-full font-bold text-lg hover:bg-orange-600 transition-all transform hover:scale-105 shadow-2xl">
                            <span class="flex items-center">
                                <i class="fas fa-shopping-cart mr-3"></i>
                                Découvrir les produits locaux
                                <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                            </span>
                        </a>
                        <a href="{{ route('pages.vendors') }}" class="px-8 py-4 bg-white text-orange-500 rounded-full font-bold text-lg hover:bg-orange-50 transition-all border-2 border-orange-500">
                            <i class="fas fa-store mr-2"></i>
                            Devenir vendeur
                        </a>
                    </div>
                </div>
                
                <!-- COLONNE DROITE - IMAGE ILLUSTRATIVE -->
                <div class="flex items-center justify-center">
                    <div class="relative w-full h-full">
                        <img src="{{ asset('img/R.jpeg') }}" 
                             alt="Vendeurs et clients guinéens sur {{ app_name() }}" 
                             class="w-full h-full max-h-96 object-cover rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                        
                        <!-- Overlay Subtil pour meilleure lisibilité -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent rounded-2xl"></div>
                        
                        <!-- Badge sur l'image -->
                        <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full">
                            <span class="text-sm font-bold text-orange-600">🇬🇳 Made in Guinea</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION TRANSPARENCE - STATISTIQUES RÉELLES -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-6">Transparence Totale</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Des statistiques en temps réel pour une confiance absolue dans {{ app_name() }}
                </p>
            </div>

            <!-- Statistiques Principales -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-6 text-center border border-yellow-100">
                    <div class="text-3xl font-bold text-yellow-600 mb-2">{{ number_format($totalProducts) }}+</div>
                    <div class="text-sm text-gray-600 font-medium">Produits actifs</div>
                    <div class="text-xs text-gray-500 mt-1">+{{ $platformInfo['active_products_today'] ?? 0 }} aujourd'hui</div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-6 text-center border border-blue-100">
                    <div class="text-3xl font-bold text-blue-600 mb-2">{{ number_format($totalVendors) }}+</div>
                    <div class="text-sm text-gray-600 font-medium">Vendeurs vérifiés</div>
                    <div class="text-xs text-gray-500 mt-1">+{{ $platformInfo['new_vendors_this_month'] ?? 0 }} ce mois</div>
                </div>
                
                <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-2xl p-6 text-center border border-orange-100">
                    <div class="text-3xl font-bold text-orange-600 mb-2">{{ number_format($totalUsers) }}+</div>
                    <div class="text-sm text-gray-600 font-medium">Clients satisfaits</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $platformInfo['satisfaction_rate'] ?? '98%' }} satisfaction</div>
                </div>
                
                <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-2xl p-6 text-center border border-orange-100">
                    <div class="text-3xl font-bold text-orange-600 mb-2">{{ number_format($totalOrders ?? 0) }}+</div>
                    <div class="text-sm text-gray-600 font-medium">Commandes livrées</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $platformInfo['average_delivery_time'] ?? '24-48h' }} livraison</div>
                </div>
            </div>

            <!-- Informations Plateforme -->
            <div class="bg-gray-50 rounded-2xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Informations Plateforme</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-calendar text-yellow-600 mr-2"></i>
                            <span class="text-sm text-gray-500">Date de création</span>
                        </div>
                        <p class="font-semibold text-gray-900">{{ $platformInfo['created_at'] ?? '2024-01-01' }}</p>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-code-branch text-blue-600 mr-2"></i>
                            <span class="text-sm text-gray-500">Version</span>
                        </div>
                        <p class="font-semibold text-gray-900">v{{ $platformInfo['version'] ?? '2.0.0' }}</p>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-sync text-yellow-600 mr-2"></i>
                            <span class="text-sm text-gray-500">Dernière mise à jour</span>
                        </div>
                        <p class="font-semibold text-gray-900">{{ $platformInfo['last_update'] ?? now()->format('d/m/Y') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-tags text-orange-600 mr-2"></i>
                            <span class="text-sm text-gray-500">Catégories totales</span>
                        </div>
                        <p class="font-semibold text-gray-900">{{ $platformInfo['total_categories'] ?? $categories->count() }}</p>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-shield-alt text-yellow-600 mr-2"></i>
                            <span class="text-sm text-gray-500">Transactions sécurisées</span>
                        </div>
                        <p class="font-semibold text-gray-900">{{ $platformInfo['secure_transactions'] ?? '100%' }}</p>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4">
                        <div class="flex items-center mb-2">
                            <i class="fas fa-money-bill-wave text-blue-600 mr-2"></i>
                            <span class="text-sm text-gray-500">Chiffre d'affaires</span>
                        </div>
                        <p class="font-semibold text-gray-900">{{ number_format($totalRevenue ?? 0, 0, ',', ' ') }} GNF</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION STORYTELLING - POURQUOI {{ app_name() }} -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-6">Pourquoi {{ app_name() }} ?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    Plus qu'une marketplace, un écosystème qui connecte et valorise le talent guinéen
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-handshake text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Confiance</h3>
                    <p class="text-gray-600">Transactions sécurisées et vendeurs vérifiés</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-globe-africa text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Local</h3>
                    <p class="text-gray-600">Produits authentiquement guinéens</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Communauté</h3>
                    <p class="text-gray-600">Des milliers d'utilisateurs satisfaits</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Croissance</h3>
                    <p class="text-gray-600">Opportunités pour tous les vendeurs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA FINAL AMÉLIORÉ - VENDEURS -->
    <section class="py-24 relative overflow-hidden" style="background-image: url('{{ asset('img/OIP.png') }}'); background-size: cover; background-position: center;">
        <!-- Background Overlay Premium -->
        <div class="absolute inset-0 bg-gradient-to-br from-yellow-900/80 via-orange-800/70 to-yellow-900/80"></div>
        
        <!-- Animated Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-10 right-10 w-32 h-32 bg-yellow-400/20 rounded-full animate-pulse"></div>
            <div class="absolute bottom-10 left-20 w-48 h-48 bg-white/10 rounded-full animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-16 border border-white/20">
                <h2 class="text-4xl font-bold text-white mb-6">
                    Prêt à développer votre business avec {{ app_name() }} ?
                </h2>
                <p class="text-xl text-white/90 mb-12 max-w-3xl mx-auto leading-relaxed">
                    Rejoignez des milliers de vendeurs qui font confiance à notre plateforme 
                    pour toucher plus de clients et augmenter leurs ventes.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-6 justify-center mb-12">
                    <a href="{{ route('register') }}" class="group px-10 py-4 bg-yellow-400 text-gray-900 rounded-full font-bold text-lg hover:bg-yellow-300 transition-all transform hover:scale-105 shadow-2xl">
                        <span class="flex items-center">
                            <i class="fas fa-rocket mr-3"></i>
                            Commencer à vendre
                            <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                    </a>
                    <a href="{{ route('pages.vendors') }}" class="px-10 py-4 bg-white/20 backdrop-blur-sm text-white rounded-full font-bold text-lg hover:bg-white/30 transition-all border border-white/30">
                        <i class="fas fa-info-circle mr-2"></i>
                        En savoir plus
                    </a>
                </div>

                <!-- Vendor Stats Premium -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-yellow-300 mb-2">{{ number_format($totalVendors) }}+</div>
                        <div class="text-white/80 text-lg font-medium">Vendeurs actifs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-yellow-300 mb-2">{{ number_format($totalProducts) }}+</div>
                        <div class="text-white/80 text-lg font-medium">Produits vendus</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-yellow-300 mb-2">98%</div>
                        <div class="text-white/80 text-lg font-medium">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Footer with Glass Effect -->
    <footer class="relative bg-gradient-to-br from-gray-900 via-gray-800 to-black text-white py-16 overflow-hidden">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-64 h-64 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-br from-orange-400 to-yellow-600 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-gradient-to-br from-yellow-300 to-orange-500 rounded-full blur-3xl"></div>
        </div>

        <!-- Glass Overlay -->
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/20 to-black/40"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info with Enhanced Design -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-yellow-500/25">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold bg-gradient-to-r from-yellow-400 to-orange-600 bg-clip-text text-transparent">{!! app_name('public', 'logo') !!}</span>
                    </div>
                    <p class="text-gray-300 mb-6 max-w-md leading-relaxed">
                        La marketplace N°1 en Guinée pour acheter et vendre en toute confiance. 
                        Rejoignez des milliers d'utilisateurs satisfaits et découvrez une expérience 
                        shopping unique et sécurisée.
                    </p>
                    
                    <!-- Enhanced Social Links -->
                    <div class="flex space-x-4 mb-6">
                        <a href="#" class="group relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg blur opacity-75 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative w-10 h-10 bg-gray-800/50 backdrop-blur-sm rounded-lg flex items-center justify-center border border-gray-700 group-hover:border-blue-500 transition-all">
                                <i class="fab fa-facebook-f text-white text-lg"></i>
                            </div>
                        </a>
                        <a href="#" class="group relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-pink-400 to-orange-500 rounded-lg blur opacity-75 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative w-10 h-10 bg-gray-800/50 backdrop-blur-sm rounded-lg flex items-center justify-center border border-gray-700 group-hover:border-orange-500 transition-all">
                                <i class="fab fa-instagram text-white text-lg"></i>
                            </div>
                        </a>
                        <a href="#" class="group relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-yellow-400 to-orange-600 rounded-lg blur opacity-75 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative w-10 h-10 bg-gray-800/50 backdrop-blur-sm rounded-lg flex items-center justify-center border border-gray-700 group-hover:border-yellow-500 transition-all">
                                <i class="fab fa-whatsapp text-white text-lg"></i>
                            </div>
                        </a>
                        <a href="#" class="group relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-cyan-600 rounded-lg blur opacity-75 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative w-10 h-10 bg-gray-800/50 backdrop-blur-sm rounded-lg flex items-center justify-center border border-gray-700 group-hover:border-cyan-500 transition-all">
                                <i class="fab fa-twitter text-white text-lg"></i>
                            </div>
                        </a>
                    </div>

                    <!-- Trust Badges -->
                    <div class="flex flex-wrap gap-3">
                        <div class="px-3 py-1 bg-yellow-500/20 backdrop-blur-sm border border-yellow-500/30 rounded-full text-xs text-yellow-400 flex items-center">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Paiement Sécurisé
                        </div>
                        <div class="px-3 py-1 bg-orange-500/20 backdrop-blur-sm border border-orange-500/30 rounded-full text-xs text-orange-400 flex items-center">
                            <i class="fas fa-headset mr-1"></i>
                            Support 24/7
                        </div>
                    </div>
                </div>

                <!-- Quick Links with Enhanced Design -->
                <div>
                    <h3 class="font-semibold text-lg mb-6 bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">Acheter</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="{{ route('client.catalog.index') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-shopping-bag mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Tous les produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('client.catalog.index') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-th-large mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Catégories</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('client.favorites.index') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-heart mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Favoris</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('client.orders.index') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-box mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Mes commandes</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Sell Links with Enhanced Design -->
                <div>
                    <h3 class="font-semibold text-lg mb-6 bg-gradient-to-r from-yellow-400 to-orange-400 bg-clip-text text-transparent">Vendre</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="{{ route('pages.vendors') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-rocket mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Devenir vendeur</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('vendeur.dashboard') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-tachometer-alt mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Tableau de bord</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('vendeur.products.index') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-box-open mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Mes produits</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('vendeur.orders.index') }}" class="text-gray-300 hover:text-white transition-all duration-300 flex items-center group">
                                <i class="fas fa-chart-line mr-2 text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                <span class="group-hover:translate-x-1 transition-transform inline-block">Ventes</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Enhanced Bottom Footer -->
            <div class="border-t border-gray-700/50 pt-8 mt-12">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-400 text-sm mb-4 md:mb-0 flex items-center">
                        <span>© {{ date('Y') }}</span>
                        <span class="mx-2">•</span>
                        <span class="bg-gradient-to-r from-yellow-400 to-orange-600 bg-clip-text text-transparent font-semibold">{!! app_name() !!}</span>
                        <span class="mx-2">•</span>
                        <span>Marketplace de confiance en Guinée</span>
                    </div>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <a href="{{ route('pages.terms') }}" class="text-gray-400 hover:text-white transition-all duration-300 hover:text-yellow-400">Conditions d'utilisation</a>
                        <a href="{{ route('pages.privacy') }}" class="text-gray-400 hover:text-white transition-all duration-300 hover:text-orange-400">Politique de confidentialité</a>
                        <a href="{{ route('pages.cookies') }}" class="text-gray-400 hover:text-white transition-all duration-300 hover:text-yellow-400">Cookies</a>
                        <a href="{{ route('pages.legal') }}" class="text-gray-400 hover:text-white transition-all duration-300 hover:text-orange-400">Mentions légales</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Menu Script -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileSearch');
            menu.classList.toggle('hidden');
        }
    </script>
    @livewireScripts
</body>
</html>
