{{-- resources/views/vendeur/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', app_name('vendor', 'title'))</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @livewireStyles
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        
        /* Thème Jaune/Orange Vendeur */
        .vendor-gradient {
            background: linear-gradient(135deg, #FFC107 0%, #FFA726 50%, #FF8F00 100%);
        }
        .vendor-gradient-text {
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
        
        /* Animations */
        .sidebar-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-item:hover {
            transform: translateX(8px);
            background: rgba(255, 193, 7, 0.1);
        }
        .sidebar-item.active {
            background: rgba(255, 193, 7, 0.15);
            border-left: 4px solid #FFC107;
        }
        
        /* Styles spécifiques pour le sidebar fixe */
        .vendor-sidebar-fixed {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 50;
        }
        
        @media (min-width: 1024px) {
            .vendor-sidebar-fixed {
                transform: translateX(0) !important;
            }
        }
        .floating {
            animation: floating 4s ease-in-out infinite;
        }
        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        /* Background Blanc */
        .main-bg {
            background: #FFFFFF;
        }
        
        /* Garantir l'affichage correct du nom GuinéeMall */
        .brand-name {
            font-family: 'Inter', sans-serif;
            font-weight: 900;
            line-height: 1.2;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
            unicode-bidi: normal;
        }
        
        .brand-name-container {
            display: flex;
            align-items: center;
            white-space: nowrap;
            overflow: visible;
        }
        
        /* Éviter la coupure du texte avec accent */
        .brand-accent {
            unicode-bidi: embed;
            text-combine-upright: none;
            letter-spacing: normal;
        }
    </style>
</head>
<body class="main-bg min-h-screen antialiased overflow-x-hidden">
    {{-- Header Moderne --}}
    <header class="bg-white/90 backdrop-blur-lg shadow-lg border-b border-gray-200 sticky top-0 z-50 lg:ml-64">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-3">
                {{-- Logo --}}
                <a href="{{ route('vendeur.dashboard') }}" class="flex items-center space-x-3 group">
                    @if(auth()->user()->vendor && auth()->user()->vendor->image_url)
                        <img src="{{ auth()->user()->vendor->image_url }}" 
                             alt="{{ auth()->user()->vendor->shop_name }}"
                             class="w-10 h-10 rounded-xl object-cover border-2 border-white shadow-md group-hover:rotate-12 transition-transform">
                    @else
                        <div class="w-10 h-10 vendor-gradient rounded-xl flex items-center justify-center floating group-hover:rotate-12 transition-transform">
                            <i class="fas fa-store text-white text-lg"></i>
                        </div>
                    @endif
                    <div class="brand-name-container">
                        <span class="brand-name text-2xl">{!! app_name('vendor', 'logo') !!}</span>
                        <span class="text-sm text-gray-500 ml-2">Vendeur</span>
                    </div>
                </a>

                {{-- Navigation droite --}}
                <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4">
                    <button id="vendorMenuButton" type="button" class="lg:hidden w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all" aria-label="Ouvrir le menu">
                        <i class="fas fa-bars text-gray-600"></i>
                    </button>
                    
                    {{-- Messages et Notifications --}}
                    <div class="flex items-center space-x-2">
                        <!-- Notifications de chat -->
                        @livewire('chat-notification')
                        
                        <!-- Messages -->
                        <a href="{{ route('chat.index') }}" 
                           class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all"
                           title="Messages">
                            <i class="fas fa-comments text-gray-600"></i>
                        </a>
                    </div>

                    {{-- Notifications --}}
                    <div class="relative">
                        <a href="{{ route('vendeur.notifications.index') }}" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all">
                            <i class="fas fa-bell text-gray-600"></i>
                            @if(auth()->user()->vendor && auth()->user()->vendor->products()->where('stock', '<=', 5)->count() > 0)
                                <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                            @endif
                        </a>
                    </div>

                    {{-- Menu profil --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-2 sm:space-x-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-3 sm:px-4 py-2 rounded-full hover:shadow-lg transition-all">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-white/20">
                                @if(auth()->user()->vendor && auth()->user()->vendor->image_url)
                                    <img src="{{ auth()->user()->vendor->image_url }}" 
                                         alt="{{ auth()->user()->vendor->shop_name }}"
                                         class="w-full h-full object-cover rounded-full">
                                @else
                                    <i class="fas fa-user text-white text-sm"></i>
                                @endif
                            </div>
                            <span class="font-medium hidden sm:inline">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" 
                             @click.away="open = false"
                             x-cloak
                             x-transition
                             class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl py-2 z-50 border border-gray-100">
                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-bold text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">Vendeur</p>
                                @if(auth()->user()->vendor)
                                    <p class="text-xs text-yellow-600">{{ auth()->user()->vendor->shop_name }}</p>
                                @endif
                            </div>
                            <a href="{{ route('vendeur.dashboard') }}" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-tachometer-alt w-4 text-yellow-600"></i>
                                <span class="ml-3">Tableau de bord</span>
                            </a>
                            <a href="{{ route('vendeur.products.index') }}" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-box w-4 text-yellow-600"></i>
                                <span class="ml-3">Mes produits</span>
                            </a>
                            <a href="{{ route('vendeur.orders.index') }}" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-shopping-cart w-4 text-yellow-600"></i>
                                <span class="ml-3">Mes commandes</span>
                            </a>
                            <a href="{{ route('chat.index') }}" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-comments w-4 text-yellow-600"></i>
                                <span class="ml-3">Messages clients</span>
                            </a>
                            <hr class="my-2">
                            <a href="{{ route('vendeur.profile.index') }}" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-store w-4 text-yellow-600"></i>
                                <span class="ml-3">Ma boutique</span>
                            </a>
                            <a href="{{ route('home') }}" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-home w-4 text-yellow-600"></i>
                                <span class="ml-3">Voir le site</span>
                            </a>
                            <hr class="my-2">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex items-center w-full px-4 py-2 hover:bg-red-50 text-red-600 transition-colors">
                                    <i class="fas fa-sign-out-alt w-4"></i>
                                    <span class="ml-3">Déconnexion</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Contenu principal --}}
    <div id="vendorOverlay" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden"></div>

    <main class="flex flex-col lg:flex-row">
        {{-- Sidebar Moderne --}}
        <aside id="vendorSidebar" class="vendor-sidebar-fixed w-64 bg-white/80 backdrop-blur-lg shadow-xl border-r border-gray-200 overflow-y-auto transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0">
            @include('vendeur.partials.sidebar')
        </aside>

        {{-- Contenu --}}
        <div class="flex-1 p-4 sm:p-6 lg:ml-64">
            {{-- Messages flash --}}
            @include('vendeur.partials.alerts')
            
            @yield('content')
        </div>
    </main>

    @stack('scripts')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('vendorSidebar');
        const overlay = document.getElementById('vendorOverlay');
        const menuButton = document.getElementById('vendorMenuButton');

        const openSidebar = () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        const closeSidebar = () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        if (menuButton) {
            menuButton.addEventListener('click', openSidebar);
        }
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
    });
    </script>
    @livewireScripts
    @livewireScriptConfig
</body>
</html>
