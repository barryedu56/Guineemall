<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app_name('public', 'title') }} - {{ $pageTitle ?? 'Marketplace N°1 en Guinée' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
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
        box-shadow: 0 12px 24px rgba(5, 150, 105, 0.15);
        border-color: rgba(5, 150, 105, 0.2);
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
    <!-- Public Navigation Header -->
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

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-yellow-600 font-medium transition-colors">
                        <i class="fas fa-home mr-2"></i>Accueil
                    </a>
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
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden">
                    <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-yellow-600 p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-bold">{!! app_name('public', 'logo') !!}</span>
                    </div>
                    <p class="text-gray-300 mb-4">
                        La marketplace N°1 en Guinée pour acheter et vendre en toute confiance.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Liens rapides</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="text-gray-300 hover:text-white transition-colors">Accueil</a></li>
                        <li><a href="{{ route('client.catalog.index') }}" class="text-gray-300 hover:text-white transition-colors">Produits</a></li>
                        <li><a href="{{ route('pages.terms') }}" class="text-gray-300 hover:text-white transition-colors">Conditions</a></li>
                        <li><a href="{{ route('pages.privacy') }}" class="text-gray-300 hover:text-white transition-colors">Confidentialité</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li><i class="fas fa-envelope mr-2"></i>contact@guineemall.com</li>
                        <li><i class="fas fa-phone mr-2"></i>+224 622 123 456</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i>Conakry, Guinée</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} {{ app_name() }}. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            // Mobile menu toggle functionality
            console.log('Mobile menu toggle');
        }
    </script>
    @livewireScripts
</body>
</html>
