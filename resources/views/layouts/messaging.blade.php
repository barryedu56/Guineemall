<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Messagerie - GuinéeMall')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'guinea-orange': {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                        'guinea-yellow': {
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12',
                        }
                    },
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'slide-in': 'slideIn 0.3s ease-out',
                        'pulse-slow': 'pulse 3s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideIn: {
                            '0%': { opacity: '0', transform: 'translateX(-20px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                    },
                }
            }
        }
    </script>
    
    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-inter">
    <!-- Header GuinéeMall Messagerie -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo et Nom -->
                <div class="flex items-center space-x-4">
                    <!-- Logo GuinéeMall -->
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-guinea-orange-500 to-guinea-yellow-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-sm font-bold"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900">GuinéeMall</span>
                    </div>
                    
                    <!-- Séparateur -->
                    <div class="h-8 w-px bg-gray-300"></div>
                    
                    <!-- Titre Messagerie -->
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-comments text-guinea-orange-500"></i>
                        <span class="text-lg font-semibold text-gray-700">Messagerie</span>
                    </div>
                </div>

                <!-- Navigation et Profil -->
                <div class="flex items-center space-x-4">
                    <!-- Navigation rapide -->
                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('chat.index') }}" 
                           class="px-3 py-2 rounded-lg text-sm font-medium bg-guinea-orange-50 text-guinea-orange-600 border border-guinea-orange-200">
                            <i class="fas fa-comments mr-2"></i>
                            Messages
                        </a>
                        @if(auth()->user()->role === 'client')
                            <a href="{{ route('client.dashboard') }}" 
                               class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
                                <i class="fas fa-tachometer-alt mr-2"></i>
                                Dashboard
                            </a>
                        @elseif(auth()->user()->role === 'vendeur')
                            <a href="{{ route('vendeur.dashboard') }}" 
                               class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
                                <i class="fas fa-store mr-2"></i>
                                Boutique
                            </a>
                        @elseif(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" 
                               class="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
                                <i class="fas fa-shield-alt mr-2"></i>
                                Admin
                            </a>
                        @endif
                    </nav>

                    <!-- Notifications Messages -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="relative p-2 text-gray-600 hover:text-guinea-orange-500 transition-colors">
                            <i class="fas fa-bell text-lg"></i>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        
                        <!-- Dropdown Notifications -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             @click.away="open = false"
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <div class="p-4 text-center text-gray-500">
                                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                                    <p class="text-sm">Aucune notification</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profil Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <!-- Avatar -->
                            <div class="w-8 h-8 bg-gradient-to-br from-guinea-orange-400 to-guinea-yellow-400 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                            </div>
                            <!-- Infos utilisateur -->
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 capitalize">{{ auth()->user()->role }}</p>
                            </div>
                            <!-- Icône dropdown -->
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </button>
                        
                        <!-- Dropdown Profil -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 transform scale-100"
                             x-transition:leave-end="opacity-0 transform scale-95"
                             @click.away="open = false"
                             class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <!-- Header profil -->
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-guinea-orange-400 to-guinea-yellow-400 rounded-full flex items-center justify-center">
                                        <span class="text-white font-bold">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Menu actions -->
                            <div class="py-2">
                                @if(auth()->user()->role === 'client')
                                    <a href="{{ route('client.profile.edit') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-user mr-3 text-gray-400"></i>
                                        Mon profil
                                    </a>
                                    <a href="{{ route('client.orders.index') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-shopping-bag mr-3 text-gray-400"></i>
                                        Mes commandes
                                    </a>
                                @elseif(auth()->user()->role === 'vendeur')
                                    <a href="{{ route('vendeur.profile.edit') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-user mr-3 text-gray-400"></i>
                                        Mon profil
                                    </a>
                                    <a href="{{ route('vendeur.products.index') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-box mr-3 text-gray-400"></i>
                                        Mes produits
                                    </a>
                                @elseif(auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.profile.edit') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-user mr-3 text-gray-400"></i>
                                        Mon profil
                                    </a>
                                    <a href="{{ route('admin.messages.index') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-comments mr-3 text-gray-400"></i>
                                        Messages admin
                                    </a>
                                @endif
                                
                                <div class="border-t border-gray-200 my-2"></div>
                                
                                @if(auth()->user()->role === 'client')
                                    <a href="{{ route('client.profile.edit') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-cog mr-3 text-gray-400"></i>
                                        Paramètres du profil
                                    </a>
                                @elseif(auth()->user()->role === 'vendeur')
                                    <a href="{{ route('vendeur.profile.edit') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-cog mr-3 text-gray-400"></i>
                                        Paramètres du profil
                                    </a>
                                @elseif(auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.profile.edit') }}" 
                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        <i class="fas fa-cog mr-3 text-gray-400"></i>
                                        Paramètres du profil
                                    </a>
                                @endif
                                
                                <form method="POST" action="{{ route('logout') }}" class="block">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt mr-3"></i>
                                        Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="h-[calc(100vh-4rem)]">
        @yield('content')
    </main>

    <!-- Footer optionnel -->
    @if(isset($showFooter) && $showFooter)
    <footer class="bg-white border-t border-gray-200 py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} GuinéeMall. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    @endif

    <!-- Scripts -->
    <script>
        // Variables globales pour l'application
        window.GuineaMall = {
            userId: {{ auth()->id() }},
            userName: '{{ auth()->user()->name }}',
            userRole: '{{ auth()->user()->role }}',
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
    
    @stack('scripts')
</body>
</html>
