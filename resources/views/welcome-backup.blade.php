<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'GuinéeMall') }} - La marketplace N°1 en Guinée</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Thème Jaune/Orange */
        .yellow-gradient {
            background: linear-gradient(135deg, #FFC107 0%, #FFA726 50%, #FF9800 100%);
        }
        .yellow-gradient-text {
            background: linear-gradient(135deg, #FFC107 0%, #FFA726 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Animations avancées */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.3);
        }
        .floating {
            animation: floating 4s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(2deg); }
            50% { transform: translateY(-10px) rotate(-1deg); }
            75% { transform: translateY(-15px) rotate(1deg); }
        }
        .pulse-glow {
            animation: pulseGlow 2s ease-in-out infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.5); }
            50% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.8); }
        }
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .zoom-in {
            animation: zoomIn 0.5s ease-out;
        }
        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Background animé */
        .animated-bg {
            background: linear-gradient(-45deg, #f0fdf4, #dcfce7, #bbf7d0, #86efac, #4ade80, #22c55e);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Boutons vivants */
        .btn-alive {
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        .btn-alive::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn-alive:hover::before {
            width: 300px;
            height: 300px;
        }
        
        /* Images avec effets */
        .image-hover {
            transition: all 0.4s ease;
            overflow: hidden;
        }
        .image-hover img {
            transition: transform 0.6s ease;
        }
        .image-hover:hover img {
            transform: scale(1.1) rotate(2deg);
        }
        
        /* Loading animation */
        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body class="animated-bg min-h-screen antialiased">

    <!-- Navigation Transparente avec Thème Vert -->
    <nav class="sticky top-0 z-50 glass-effect shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo Animé -->
                <div class="flex items-center space-x-3 group cursor-pointer" onclick="window.location.href='/'">
                    <div class="w-12 h-12 yellow-gradient rounded-xl flex items-center justify-center shadow-lg floating group-hover:rotate-12 transition-transform">
                        <i class="fas fa-shopping-bag text-white text-xl"></i>
                    </div>
                    <span class="text-2xl font-black text-gray-800">Guinée<span class="yellow-gradient-text">Mall</span></span>
                </div>

                <!-- Barre de recherche dynamique -->
                <div class="hidden lg:flex flex-1 max-w-2xl mx-8">
                    <form action="{{ route('client.catalog.index') }}" method="GET" class="relative w-full">
                        <input type="text" 
                               name="search"
                               placeholder="Rechercher {{ $totalProducts }}+ produits..." 
                               class="w-full px-4 py-3 pl-12 pr-4 text-gray-800 bg-white/80 backdrop-blur border border-yellow-200 rounded-xl focus:outline-none focus:border-yellow-500 focus:bg-white transition-all">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-yellow-600"></i>
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-2 yellow-gradient text-white rounded-lg hover:opacity-90 transition-all btn-alive">
                            <i class="fas fa-search mr-2"></i>Rechercher
                        </button>
                    </form>
                </div>

                <!-- Navigation Links avec animations -->
                <div class="hidden md:flex items-center space-x-6">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-gray-700 hover:text-yellow-600 font-bold transition-all transform hover:scale-105">
                            <i class="fas fa-tachometer-alt mr-2"></i>Tableau de bord
                        </a>
                        <a href="{{ route('client.cart.index') }}" class="text-gray-700 hover:text-yellow-600 font-bold transition-all transform hover:scale-105 flex items-center">
                            <i class="fas fa-shopping-cart mr-2"></i>Panier
                            @if(auth()->check() && auth()->user()->cartItems && auth()->user()->cartItems->count() > 0)
                                <span class="ml-1 text-gray-800 font-semibold">{{ auth()->user()->cartItems->count() }}</span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-yellow-600 font-bold transition-all transform hover:scale-105">
                            <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                        </a>
                        <a href="{{ route('register') }}" class="px-5 py-2.5 yellow-gradient text-white font-bold rounded-xl hover:opacity-90 transition-all shadow-lg btn-alive transform hover:scale-105">
                            <i class="fas fa-user-plus mr-2"></i>Inscription
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-yellow-600 transform hover:scale-110 transition-all">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section Dynamique avec Thème Vert -->
    <section class="relative overflow-hidden pt-20 pb-32 lg:pt-32 lg:pb-40">
        <!-- Background Elements Animés -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 floating"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-orange-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 floating" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-yellow-200 rounded-full mix-blend-multiply filter blur-xl opacity-20 floating" style="animation-delay: 4s;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Content Dynamique -->
                <div class="slide-in">
                    <div class="inline-flex items-center px-4 py-2 bg-yellow-100 rounded-full border border-yellow-200 mb-6 zoom-in">
                        <span class="flex h-2 w-2 rounded-full bg-yellow-600 mr-2 animate-pulse"></span>
                        <span class="text-sm font-bold text-yellow-700 uppercase tracking-wider">🎉 {{ $totalProducts }}+ produits disponibles</span>
                    </div>
                    
                    <h1 class="text-5xl lg:text-7xl font-black text-gray-800 mb-6 leading-[1.1]">
                        La marketplace 
                        <span class="yellow-gradient-text">N°1 en Guinée</span>
                    </h1>

                    <p class="text-xl text-gray-700 mb-8 leading-relaxed max-w-lg">
                        Découvrez {{ $totalProducts }}+ produits chez {{ $totalVendors }}+ vendeurs. 
                        Rejoignez {{ $totalUsers }}+ clients satisfaits sur la plateforme locale la plus dynamique.
                    </p>

                    <!-- Statistiques Réelles -->
                    <div class="grid grid-cols-3 gap-6 mb-8">
                        <div class="text-center p-4 bg-white/60 backdrop-blur rounded-2xl card-hover">
                            <div class="text-3xl font-black text-yellow-600">{{ number_format($totalProducts) }}</div>
                            <div class="text-sm font-bold text-gray-600">Produits</div>
                        </div>
                        <div class="text-center p-4 bg-white/60 backdrop-blur rounded-2xl card-hover">
                            <div class="text-3xl font-black text-yellow-600">{{ number_format($totalVendors) }}</div>
                            <div class="text-sm font-bold text-gray-600">Vendeurs</div>
                        </div>
                        <div class="text-center p-4 bg-white/60 backdrop-blur rounded-2xl card-hover">
                            <div class="text-3xl font-black text-yellow-600">{{ number_format($totalUsers) }}K+</div>
                            <div class="text-sm font-bold text-gray-600">Clients</div>
                        </div>
                    </div>

                    <!-- CTA Buttons Dynamiques -->
                    @guest
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="{{ route('register') }}" class="group px-8 py-4 yellow-gradient text-white font-bold rounded-2xl hover:opacity-90 transition-all flex items-center justify-center shadow-xl btn-alive transform hover:scale-105">
                                <i class="fas fa-rocket mr-3"></i>
                                Commencer gratuitement
                                <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                            <a href="{{ route('client.catalog.index') }}" class="px-8 py-4 bg-white text-gray-800 font-bold rounded-2xl border-2 border-yellow-500 hover:bg-yellow-50 transition-all transform hover:scale-105">
                                <i class="fas fa-shopping-bag mr-3"></i>
                                Parcourir les produits
                            </a>
                        </div>
                    @endguest
                </div>

                <!-- Produits en Vedette Dynamiques -->
                <div class="relative">
                    <div class="relative z-10">
                        <div class="bg-white/80 backdrop-blur-lg rounded-3xl shadow-2xl p-6 card-hover">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 text-center">🔥 Produits en vedette</h3>
                            <div class="grid grid-cols-2 gap-4">
                                @forelse(isset($featuredProducts) ? $featuredProducts->take(4) : collect() as $product)
                                    <a href="{{ route('client.catalog.show', $product) }}" class="group">
                                        <div class="bg-white rounded-2xl p-3 border border-gray-100 hover:border-yellow-500 transition-all">
                                            <div class="aspect-square rounded-xl overflow-hidden mb-2 image-hover">
                                                @if($product->image_url)
                                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400 text-2xl"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <h4 class="text-xs font-bold text-gray-800 truncate">{{ $product->name }}</h4>
                                            <p class="text-sm font-black text-yellow-600">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                                        </div>
                                    </a>
                                @empty
                                    <!-- Produits de démonstration -->
                                    @foreach([
                                        ['name' => 'iPhone 15 Pro', 'price' => 1500000, 'icon' => '📱'],
                                        ['name' => 'Robe Africaine', 'price' => 75000, 'icon' => '👗'],
                                        ['name' => 'Ordinateur Portable', 'price' => 2500000, 'icon' => '💻'],
                                        ['name' => 'Montre Connectée', 'price' => 350000, 'icon' => '⌚'],
                                    ] as $demoProduct)
                                        <div class="bg-white rounded-2xl p-3 border border-gray-100">
                                            <div class="aspect-square rounded-xl overflow-hidden mb-2 bg-gradient-to-br from-yellow-100 to-orange-100 flex items-center justify-center">
                                                <span class="text-4xl">{{ $demoProduct['icon'] }}</span>
                                            </div>
                                            <h4 class="text-xs font-bold text-gray-800 truncate">{{ $demoProduct['name'] }}</h4>
                                            <p class="text-sm font-black text-yellow-600">{{ number_format($demoProduct['price'], 0, ',', ' ') }} GNF</p>
                                        </div>
                                    @endforeach
                                @endforelse
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute top-4 -right-4 w-20 h-20 yellow-gradient rounded-2xl flex items-center justify-center shadow-lg floating pulse-glow">
                        <i class="fas fa-star text-white text-2xl"></i>
                    </div>
                    <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-emerald-500 rounded-2xl flex items-center justify-center shadow-lg floating" style="animation-delay: 1s;">
                        <i class="fas fa-heart text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

        <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-black text-gray-900 mb-4">
                    Pourquoi choisir <span class="gradient-text">GuinéeMall</span> ?
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    La plateforme locale avec des standards internationaux pour votre sécurité et votre confort
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="group bg-gradient-to-br from-yellow-50 to-white p-8 rounded-3xl border border-yellow-100 hover:shadow-2xl transition-all card-hover">
                    <div class="w-16 h-16 hero-gradient rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Paiements Sécurisés</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Transactions cryptées et protection contre la fraude. Vos paiements sont en sécurité avec nos partenaires de confiance.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="group bg-gradient-to-br from-blue-50 to-white p-8 rounded-3xl border border-blue-100 hover:shadow-2xl transition-all card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-truck text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Livraison Rapide</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Livraison à domicile partout en Guinée en 24-48h. Suivez votre colis en temps réel.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="group bg-gradient-to-br from-yellow-50 to-white p-8 rounded-3xl border border-yellow-100 hover:shadow-2xl transition-all card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Support 24/7</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Notre équipe locale est disponible pour vous aider à tout moment. Chat, email et téléphone.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="group bg-gradient-to-br from-orange-50 to-white p-8 rounded-3xl border border-orange-100 hover:shadow-2xl transition-all card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-tag text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Meilleurs Prix</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Comparez les prix et profitez des meilleures offres du marché. Promotions exclusives chaque semaine.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="group bg-gradient-to-br from-pink-50 to-white p-8 rounded-3xl border border-pink-100 hover:shadow-2xl transition-all card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-store text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Vendeurs Vérifiés</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Tous nos vendeurs sont vérifiés et notés par la communauté. Achetez en toute confiance.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="group bg-gradient-to-br from-orange-50 to-white p-8 rounded-3xl border border-orange-100 hover:shadow-2xl transition-all card-hover">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-undo text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Retours Faciles</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Insatisfait ? Retournez facilement vos produits sous 14 jours. Remboursement garanti.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Catégories Dynamiques avec Thème Vert -->
    <section class="py-20 bg-white/60 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="text-4xl font-black text-gray-800 mb-4">
                        Explorez nos <span class="yellow-gradient-text">catégories</span>
                    </h2>
                    <p class="text-xl text-gray-700">{{ $totalProducts }}+ produits dans {{ $categories->count() }} catégories</p>
                </div>
                <a href="{{ route('client.catalog.index') }}" class="yellow-gradient-text font-bold flex items-center transform hover:scale-105 transition-all">
                    Voir tout
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @if(isset($categories) && $categories->count() > 0)
                    @foreach($categories as $category)
                        <a href="{{ route('client.catalog.category', $category) }}" class="group">
                            <div class="bg-white/80 backdrop-blur rounded-3xl p-6 border border-gray-100 hover:border-yellow-500 hover:shadow-2xl transition-all cursor-pointer card-hover">
                                <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-orange-100 rounded-2xl flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform">
                                    @switch($category->name)
                                        @case('Électronique')
                                            📱
                                            @break
                                        @case('Vêtements & Mode')
                                            👗
                                            @break
                                        @case('Bijoux')
                                            💎
                                            @break
                                        @case('Maison & Jardin')
                                            🏠
                                            @break
                                        @case('Beauté & Santé')
                                            💄
                                            @break
                                        @case('Sports & Loisirs')
                                            🏃‍♂️
                                            @break
                                        @case('Livres & Médias')
                                            📚
                                            @break
                                        @case('Automobile')
                                            🚗
                                            @break
                                        @case('Bébés & Enfants')
                                            👶
                                            @break
                                        @case('Alimentation & Boissons')
                                            🍽️
                                            @break
                                        @case('Services')
                                            🛠️
                                            @break
                                        @default
                                            📦
                                    @endswitch
                                </div>
                                <h3 class="font-bold text-gray-800 mb-2 group-hover:text-yellow-600 transition-colors">{{ $category->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $category->products_count ?? 0 }} produits</p>
                            </div>
                        </a>
                    @endforeach
                @else
                    <!-- Catégories par défaut si aucune en base -->
                    @foreach([
                        ['name' => 'Électronique', 'icon' => '📱'],
                        ['name' => 'Vêtements & Mode', 'icon' => '👗'],
                        ['name' => 'Maison & Jardin', 'icon' => '🏠'],
                        ['name' => 'Beauté & Santé', 'icon' => '💄'],
                        ['name' => 'Sports & Loisirs', 'icon' => '🏃‍♂️'],
                        ['name' => 'Alimentation', 'icon' => '🍽️'],
                    ] as $category)
                        <a href="{{ route('client.catalog.index') }}?category={{ $category['name'] }}" class="group">
                            <div class="bg-white/80 backdrop-blur rounded-3xl p-6 border border-gray-100 hover:border-yellow-500 hover:shadow-2xl transition-all cursor-pointer card-hover">
                                <div class="w-16 h-16 bg-gradient-to-br from-yellow-100 to-orange-100 rounded-2xl flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform">
                                    {{ $category['icon'] }}
                                </div>
                                <h3 class="font-bold text-gray-800 mb-2 group-hover:text-yellow-600 transition-colors">{{ $category['name'] }}</h3>
                                <p class="text-sm text-gray-600">Découvrir</p>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <!-- Produits par Catégorie (Dynamique) -->
    @foreach($productsByCategory as $categoryId => $products)
        @if($products->count() > 0)
            @php
                $category = $categories->firstWhere('id', $categoryId);
            @endphp
            <section class="py-16 bg-gradient-to-br from-yellow-50 to-orange-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-3xl font-black text-gray-800 mb-2">
                                {{ $category->name }}
                            </h3>
                            <p class="text-gray-600">Les meilleurs produits dans cette catégorie</p>
                        </div>
                        <a href="{{ route('client.catalog.category', $category) }}" class="yellow-gradient-text font-bold flex items-center">
                            Voir plus
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        @foreach($products as $product)
                            <a href="{{ route('client.catalog.show', $product) }}" class="group">
                                <div class="bg-white rounded-2xl p-4 border border-gray-100 hover:border-yellow-500 hover:shadow-xl transition-all card-hover">
                                    <div class="aspect-square rounded-xl overflow-hidden mb-3 image-hover">
                                        {{-- TEST VISUEL FORCÉ --}}
                                        <div class="border-2 border-blue-500 p-1 mb-2">
                                            <p class="text-xs text-blue-600">DEBUG: {{ $product->image_url ?? 'NULL' }}</p>
                                        </div>
                                        
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover" style="border:2px solid blue;">
                                        @else
                                            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-bold text-gray-800 truncate mb-1">{{ $product->name }}</h4>
                                    <p class="text-xs text-gray-500 mb-2">{{ $product->vendor->name ?? 'Vendeur' }}</p>
                                    <p class="text-sm font-black text-yellow-600">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach

    <!-- CTA Section avec Thème Vert -->
    <section class="py-20 yellow-gradient relative overflow-hidden">
        <!-- Background Animation -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full floating"></div>
            <div class="absolute top-20 right-20 w-32 h-32 bg-white/5 rounded-full floating" style="animation-delay: 1s;"></div>
            <div class="absolute bottom-10 left-1/4 w-16 h-16 bg-white/10 rounded-full floating" style="animation-delay: 2s;"></div>
        </div>
        
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-4xl font-black text-white mb-6">
                Prêt à commencer à vendre ?
            </h2>
            <p class="text-xl text-yellow-50 mb-8 max-w-2xl mx-auto">
                Rejoignez {{ $totalVendors }}+ vendeurs qui font déjà confiance à GuinéeMall pour développer leur activité.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-yellow-600 font-bold rounded-2xl hover:bg-yellow-50 transition-all shadow-xl btn-alive transform hover:scale-105">
                    <i class="fas fa-store mr-3"></i>
                    Devenir vendeur
                </a>
                <a href="{{ route('client.catalog.index') }}" class="px-8 py-4 bg-orange-700 text-white font-bold rounded-2xl hover:bg-orange-800 transition-all border-2 border-yellow-500 btn-alive transform hover:scale-105">
                    <i class="fas fa-shopping-bag mr-3"></i>
                    Parcourir les produits
                </a>
            </div>
        </div>
    </section>

    <!-- Meilleurs Vendeurs (Dynamique) -->
    @if($topVendors->count() > 0)
        <section class="py-20 bg-white/80 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-black text-gray-800 mb-4">
                        Nos <span class="yellow-gradient-text">meilleurs vendeurs</span>
                    </h2>
                    <p class="text-xl text-gray-700">Découvrez les boutiques les plus populaires de GuinéeMall</p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach($topVendors as $vendor)
                        <a href="#" class="group">
                            <div class="bg-white rounded-2xl p-6 text-center border border-gray-100 hover:border-yellow-500 hover:shadow-xl transition-all card-hover">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-yellow-100 to-orange-100 rounded-full flex items-center justify-center">
                                    @if($vendor->image_url)
                                        <img src="{{ $vendor->image_url }}" alt="{{ $vendor->name }}" class="w-full h-full rounded-full object-cover">
                                    @else
                                        <i class="fas fa-store text-yellow-600 text-2xl"></i>
                                    @endif
                                </div>
                                <h4 class="font-bold text-gray-800 mb-1 truncate">{{ $vendor->name }}</h4>
                                <p class="text-sm text-yellow-600 font-bold">{{ $vendor->products_count }}+ produits</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Footer Amélioré avec Thème Vert -->
    <footer class="bg-gray-900 text-gray-300 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-12 mb-16">
                <!-- Brand -->
                <div class="col-span-2">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 yellow-gradient rounded-xl flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-xl"></i>
                        </div>
                        <span class="text-3xl font-black text-white">Guinée<span class="text-yellow-400">Mall</span></span>
                    </div>
                    <p class="text-gray-400 max-w-md leading-relaxed mb-6">
                        La marketplace N°1 en Guinée. {{ $totalProducts }}+ produits chez {{ $totalVendors }}+ vendeurs pour {{ $totalUsers }}+ clients satisfaits.
                    </p>
                    
                    <!-- Social Links -->
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-yellow-600 transition-all transform hover:scale-110">
                            <i class="fab fa-facebook-f text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-yellow-600 transition-all transform hover:scale-110">
                            <i class="fab fa-instagram text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-yellow-600 transition-all transform hover:scale-110">
                            <i class="fab fa-twitter text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center hover:bg-yellow-600 transition-all transform hover:scale-110">
                            <i class="fab fa-whatsapp text-white"></i>
                        </a>
                    </div>
                </div>

                <!-- Acheter -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Acheter</h3>
                    <ul class="space-y-4">
                        <li><a href="{{ route('client.catalog.index') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Tous les produits</a></li>
                        <li><a href="{{ route('pages.how_to_buy') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Comment acheter</a></li>
                        <li><a href="{{ route('pages.payment_methods') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Méthodes de paiement</a></li>
                        <li><a href="{{ route('pages.delivery') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Livraison</a></li>
                        <li><a href="{{ route('pages.returns') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Retours et remboursements</a></li>
                    </ul>
                </div>

                <!-- Vendre -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6">Vendre</h3>
                    <ul class="space-y-4">
                        <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Commencer à vendre</a></li>
                        <li><a href="{{ route('pages.pricing_commissions') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Tarifs et commissions</a></li>
                        <li><a href="{{ route('pages.seller_guide') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Guide du vendeur</a></li>
                        <li><a href="{{ route('pages.success_stories') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Success stories</a></li>
                        <li><a href="{{ route('pages.affiliation') }}" class="text-gray-400 hover:text-yellow-400 transition-all transform hover:translate-x-1">Affiliation</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <p class="text-gray-500 text-sm">
                            {{ date('Y') }} GuinéeMall. Marketplace de confiance en Guinée • {{ $totalProducts }}+ produits • {{ $totalVendors }}+ vendeurs
                        </p>
                    </div>
                    
                    <div class="flex flex-wrap gap-6 text-sm">
                        <a href="{{ route('pages.terms') }}" class="text-gray-500 hover:text-yellow-400 transition-all transform hover:scale-105">Conditions d'utilisation</a>
                        <a href="{{ route('pages.privacy') }}" class="text-gray-500 hover:text-yellow-400 transition-all transform hover:scale-105">Politique de confidentialité</a>
                        <a href="{{ route('pages.cookies') }}" class="text-gray-500 hover:text-yellow-400 transition-all transform hover:scale-105">Cookies</a>
                        <a href="{{ route('pages.legal') }}" class="text-gray-500 hover:text-yellow-400 transition-all transform hover:scale-105">Mentions légales</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Search (Hidden by default) -->
    <div id="mobileSearch" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="bg-white p-4">
            <div class="relative">
                <form action="{{ route('client.catalog.index') }}" method="GET">
                    <input type="text" 
                           name="search"
                           placeholder="Rechercher {{ $totalProducts }}+ produits..." 
                           class="w-full px-4 py-3 pl-12 text-gray-900 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-yellow-500">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <button onclick="document.getElementById('mobileSearch').classList.add('hidden')" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript Avancé -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            // Implementation for mobile menu
            console.log('Mobile menu toggled');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });

            // Scroll animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('slide-in');
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.card-hover').forEach(el => {
                observer.observe(el);
            });

            // Dynamic loading animation
            setTimeout(() => {
                document.querySelectorAll('.shimmer').forEach(el => {
                    el.classList.remove('shimmer');
                });
            }, 1000);

            // Parallax effect for floating elements
            window.addEventListener('scroll', () => {
                const scrolled = window.pageYOffset;
                const parallax = document.querySelectorAll('.floating');
                
                parallax.forEach(element => {
                    const speed = element.dataset.speed || 0.5;
                    element.style.transform = `translateY(${scrolled * speed}px)`;
                });
            });
        });
    </script>
</body>
</html>