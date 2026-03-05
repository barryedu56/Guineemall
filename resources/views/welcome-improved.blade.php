<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app_name('public', 'title') }} - La marketplace N°1 en Guinée</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<style>
    body { font-family: 'Inter', sans-serif; }
    
    /* Professional Yellow Theme */
    .primary-yellow { background: linear-gradient(135deg, #FFC107 0%, #FFA726 100%); }
    .light-yellow { background: linear-gradient(135deg, #FFD54F 0%, #FFC107 100%); }
    .accent-yellow { background: linear-gradient(135deg, #FFECB3 0%, #FFD54F 100%); }
    
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
        border-color: rgba(5, 150, 105, 0.3);
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
                        <div class="w-10 h-10 primary-yellow rounded-lg flex items-center justify-center">
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
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-1.5 primary-yellow text-white rounded-lg hover:opacity-90 transition-all">
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
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 primary-yellow text-white rounded-lg">
                        <i class="fas fa-search text-sm"></i>
                    </button>
                </div>
            </form>
        </div>
    </nav>

    <!-- Enhanced Hero Section -->
    <section class="relative bg-gradient-to-br from-yellow-50 via-white to-orange-50 overflow-hidden">
        <!-- Subtle Background Pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute top-10 left-10 w-32 h-32 bg-yellow-400 rounded-full"></div>
            <div class="absolute top-1/2 right-20 w-48 h-48 bg-orange-400 rounded-full"></div>
            <div class="absolute bottom-20 left-1/3 w-40 h-40 bg-yellow-500 rounded-full"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-16 lg:py-24">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Hero Content -->
                <div class="text-center lg:text-left">
                    <!-- Live Badge -->
                    <div class="inline-flex items-center px-4 py-2 trust-badge rounded-full mb-6">
                        <span class="flex h-2 w-2 bg-yellow-600 rounded-full mr-2 animate-pulse"></span>
                        <span class="text-sm font-semibold text-yellow-700">{{ $totalProducts }}+ produits disponibles</span>
                    </div>
                    
                    <!-- Main Headline -->
                    <h1 class="hero-title text-4xl lg:text-6xl font-bold text-gray-900 mb-6">
                        La marketplace 
                        <span class="text-yellow-600">N°1 en Guinée</span>
                    </h1>

                    <!-- Subtitle -->
                    <p class="hero-subtitle text-gray-600 mb-8 max-w-2xl">
                        Achetez et vendez en toute confiance sur la plateforme qui connecte 
                        <strong>{{ $totalVendors }}+ vendeurs</strong> avec des milliers de clients en Guinée.
                    </p>

                    <!-- Trust Indicators -->
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        <div class="text-center p-4 bg-white rounded-xl marketplace-card">
                            <div class="text-2xl font-bold text-yellow-600">{{ number_format($totalProducts) }}+</div>
                            <div class="text-sm text-gray-600 font-medium">Produits</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl marketplace-card">
                            <div class="text-2xl font-bold text-yellow-600">{{ number_format($totalVendors) }}+</div>
                            <div class="text-sm text-gray-600 font-medium">Vendeurs</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-xl marketplace-card">
                            <div class="text-2xl font-bold text-yellow-600">{{ number_format($totalUsers) }}+</div>
                            <div class="text-sm text-gray-600 font-medium">Clients</div>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    @guest
                        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('register') }}" class="cta-primary text-white px-8 py-3 rounded-xl font-semibold flex items-center justify-center">
                                <i class="fas fa-rocket mr-2"></i>
                                Commencer gratuitement
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                            <a href="{{ route('client.catalog.index') }}" class="cta-secondary text-yellow-600 px-8 py-3 rounded-xl font-semibold flex items-center justify-center">
                                <i class="fas fa-shopping-bag mr-2"></i>
                                Parcourir les produits
                            </a>
                        </div>
                    @endguest
                </div>

                <!-- Featured Product Showcase -->
                <div class="relative">
                    <div class="bg-white rounded-2xl shadow-xl p-6 marketplace-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900">🔥 Produits populaires</h3>
                            <a href="{{ route('client.catalog.index') }}" class="text-sm text-yellow-600 hover:text-yellow-700 font-medium">
                                Voir tout <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            @forelse(isset($featuredProducts) ? $featuredProducts->take(4) : collect() as $product)
                                <a href="{{ route('client.catalog.show', $product) }}" class="product-card bg-gray-50 rounded-xl p-3">
                                    <div class="aspect-square rounded-lg overflow-hidden mb-3 bg-white">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" 
                                                 alt="{{ $product->name }}" 
                                                 class="product-image w-full h-full">
                                        @else
                                            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 truncate mb-1">{{ $product->name }}</h4>
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-bold text-yellow-600">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                                        @if($product->vendor)
                                            <div class="text-xs text-gray-500 flex items-center">
                                                <i class="fas fa-store mr-1"></i>
                                                {{ Str::limit($product->vendor->shop_name, 10) }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <!-- Demo Products -->
                                @foreach([
                                    ['name' => 'iPhone 15 Pro', 'price' => 1500000, 'vendor' => 'TechStore', 'icon' => '📱'],
                                    ['name' => 'Robe Africaine', 'price' => 75000, 'vendor' => 'ModeGuinée', 'icon' => '👗'],
                                    ['name' => 'Ordinateur Portable', 'price' => 2500000, 'vendor' => 'ComputerPlus', 'icon' => '💻'],
                                    ['name' => 'Montre Connectée', 'price' => 350000, 'vendor' => 'WatchShop', 'icon' => '⌚'],
                                ] as $demoProduct)
                                    <div class="product-card bg-gray-50 rounded-xl p-3">
                                        <div class="aspect-square rounded-lg overflow-hidden mb-3 bg-gradient-to-br from-yellow-100 to-orange-100 flex items-center justify-center">
                                            <span class="text-3xl">{{ $demoProduct['icon'] }}</span>
                                        </div>
                                        <h4 class="text-sm font-semibold text-gray-900 truncate mb-1">{{ $demoProduct['name'] }}</h4>
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-bold text-yellow-600">{{ number_format($demoProduct['price'], 0, ',', ' ') }} GNF</p>
                                            <div class="text-xs text-gray-500">{{ $demoProduct['vendor'] }}</div>
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

    <!-- Why Choose {{ app_name() }} Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Pourquoi choisir {{ app_name() }} ?</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    La marketplace de confiance qui simplifie vos achats et ventes en Guinée
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Payment Security -->
                <div class="trust-badge p-6 rounded-xl text-center">
                    <div class="w-16 h-16 primary-yellow rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Paiements sécurisés</h3>
                    <p class="text-gray-600 text-sm">Transactions protégées et remboursées en cas de problème</p>
                </div>

                <!-- Fast Delivery -->
                <div class="trust-badge p-6 rounded-xl text-center">
                    <div class="w-16 h-16 primary-yellow rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Livraison rapide</h3>
                    <p class="text-gray-600 text-sm">Réception de vos commandes en 24-48h à Conakry et principales villes</p>
                </div>

                <!-- 24/7 Support -->
                <div class="trust-badge p-6 rounded-xl text-center">
                    <div class="w-16 h-16 primary-yellow rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Support 24/7</h3>
                    <p class="text-gray-600 text-sm">Assistance disponible par téléphone, email et chat en direct</p>
                </div>

                <!-- Best Prices -->
                <div class="trust-badge p-6 rounded-xl text-center">
                    <div class="w-16 h-16 primary-yellow rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tag text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Meilleurs prix</h3>
                    <p class="text-gray-600 text-sm">Comparez les prix et bénéficiez des meilleures offres du marché</p>
                </div>

                <!-- Verified Vendors -->
                <div class="trust-badge p-6 rounded-xl text-center">
                    <div class="w-16 h-16 primary-yellow rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Vendeurs vérifiés</h3>
                    <p class="text-gray-600 text-sm">Tous nos vendeurs sont authentifiés et notés par la communauté</p>
                </div>

                <!-- Easy Returns -->
                <div class="trust-badge p-6 rounded-xl text-center">
                    <div class="w-16 h-16 primary-yellow rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-undo text-white text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Retours faciles</h3>
                    <p class="text-gray-600 text-sm">Politique de retour simple et satisfait ou remboursé</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Explorer par catégories</h2>
                <p class="text-lg text-gray-600">Trouvez exactement ce que vous cherchez</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse($categories as $category)
                    <a href="{{ route('client.catalog.index', ['category' => $category->slug]) }}" class="category-card bg-white p-6 rounded-xl text-center group">
                        <div class="w-16 h-16 light-green rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-{{ $category->icon ?? 'tag' }} text-white text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1">{{ $category->name }}</h3>
                        <p class="text-sm text-gray-500">{{ $category->products_count ?? 0 }} produits</p>
                    </a>
                @empty
                    <!-- Default Categories -->
                    @foreach([
                        ['name' => 'Électronique', 'count' => 150, 'icon' => 'laptop'],
                        ['name' => 'Mode & Vêtements', 'count' => 280, 'icon' => 'tshirt'],
                        ['name' => 'Maison & Jardin', 'count' => 95, 'icon' => 'home'],
                        ['name' => 'Beauté & Santé', 'count' => 120, 'icon' => 'spa'],
                    ] as $category)
                        <a href="{{ route('client.catalog.index') }}" class="category-card bg-white p-6 rounded-xl text-center group">
                            <div class="w-16 h-16 light-green rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <i class="fas fa-{{ $category['icon'] }} text-white text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900 mb-1">{{ $category['name'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $category['count'] }}+ produits</p>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <!-- Products by Category -->
    @if(!empty($productsByCategory))
        @foreach($productsByCategory as $categoryId => $products)
            @if($products->count() > 0)
                <section class="py-16 bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    {{ $products->first()->category->name ?? 'Produits populaires' }}
                                </h2>
                                <p class="text-gray-600 mt-1">Les meilleurs choix de nos vendeurs</p>
                            </div>
                            <a href="{{ route('client.catalog.index', ['category' => $products->first()->category->slug ?? '']) }}" 
                               class="text-yellow-600 hover:text-yellow-700 font-medium flex items-center">
                                Voir tout 
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>

                        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach($products as $product)
                                <a href="{{ route('client.catalog.show', $product) }}" class="product-card bg-white rounded-xl overflow-hidden border border-gray-100">
                                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" 
                                                 alt="{{ $product->name }}" 
                                                 class="product-image w-full h-full">
                                        @else
                                            <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-3xl"></i>
                                            </div>
                                        @endif
                                        @if($product->isOutOfStock())
                                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                                    Rupture
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold text-gray-900 truncate mb-2">{{ $product->name }}</h3>
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="text-lg font-bold text-yellow-600">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                                            @if($product->isLowStock())
                                                <span class="text-xs text-orange-600 font-medium">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>Stock bas
                                                </span>
                                            @endif
                                        </div>
                                        @if($product->vendor)
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i class="fas fa-store mr-1"></i>
                                                {{ Str::limit($product->vendor->shop_name, 15) }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif
        @endforeach
    @endif

    <!-- Become a Vendor CTA -->
    <section class="py-20 primary-yellow relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute bottom-10 left-20 w-32 h-32 bg-white rounded-full"></div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-2xl p-12">
                <h2 class="text-3xl font-bold text-white mb-4">
                    Prêt à développer votre business avec {{ app_name() }} ?
                </h2>
                <p class="text-xl text-white text-opacity-90 mb-8 max-w-2xl mx-auto">
                    Rejoignez des milliers de vendeurs qui font confiance à notre plateforme 
                    pour toucher plus de clients et augmenter leurs ventes.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="bg-white text-yellow-600 px-8 py-3 rounded-xl font-semibold hover:bg-gray-50 transition-colors">
                        <i class="fas fa-rocket mr-2"></i>
                        Commencer à vendre
                    </a>
                    <a href="{{ route('pages.vendors') }}" class="border-2 border-white text-white px-8 py-3 rounded-xl font-semibold hover:bg-white hover:bg-opacity-10 transition-colors">
                        <i class="fas fa-info-circle mr-2"></i>
                        En savoir plus
                    </a>
                </div>

                <!-- Vendor Stats -->
                <div class="grid grid-cols-3 gap-6 mt-12">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ number_format($totalVendors) }}+</div>
                        <div class="text-white text-opacity-80 text-sm">Vendeurs actifs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">{{ number_format($totalProducts) }}+</div>
                        <div class="text-white text-opacity-80 text-sm">Produits vendus</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-white">98%</div>
                        <div class="text-white text-opacity-80 text-sm">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 primary-yellow rounded-lg flex items-center justify-center">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-bold">{!! app_name('public', 'logo') !!}</span>
                    </div>
                    <p class="text-gray-400 mb-4 max-w-md">
                        La marketplace N°1 en Guinée pour acheter et vendre en toute confiance. 
                        Rejoignez des milliers d'utilisateurs satisfaits.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Acheter</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('client.catalog.index') }}" class="text-gray-400 hover:text-white transition-colors">Tous les produits</a></li>
                        <li><a href="{{ route('client.catalog.index') }}" class="text-gray-400 hover:text-white transition-colors">Catégories</a></li>
                        <li><a href="{{ route('client.favorites.index') }}" class="text-gray-400 hover:text-white transition-colors">Favoris</a></li>
                        <li><a href="{{ route('client.orders.index') }}" class="text-gray-400 hover:text-white transition-colors">Mes commandes</a></li>
                    </ul>
                </div>

                <!-- Sell Links -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Vendre</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition-colors">Devenir vendeur</a></li>
                        <li><a href="{{ route('vendeur.dashboard') }}" class="text-gray-400 hover:text-white transition-colors">Tableau de bord</a></li>
                        <li><a href="{{ route('vendeur.products.index') }}" class="text-gray-400 hover:text-white transition-colors">Mes produits</a></li>
                        <li><a href="{{ route('vendeur.orders.index') }}" class="text-gray-400 hover:text-white transition-colors">Ventes</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-800 pt-8 mt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-400 text-sm mb-4 md:mb-0">
                        © {{ date('Y') }} {{ app_name() }}. Marketplace de confiance en Guinée.
                    </div>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <a href="{{ route('pages.terms') }}" class="text-gray-400 hover:text-white transition-colors">Conditions d'utilisation</a>
                        <a href="{{ route('pages.privacy') }}" class="text-gray-400 hover:text-white transition-colors">Politique de confidentialité</a>
                        <a href="{{ route('pages.cookies') }}" class="text-gray-400 hover:text-white transition-colors">Cookies</a>
                        <a href="{{ route('pages.legal') }}" class="text-gray-400 hover:text-white transition-colors">Mentions légales</a>
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
</body>
</html>
