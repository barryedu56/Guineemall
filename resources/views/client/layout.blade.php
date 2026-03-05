{{-- resources/views/client/layout.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GuinéeMall - Marketplace Guinéenne')</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 overflow-x-hidden">
    {{-- Header --}}
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 py-3">
                {{-- Logo --}}
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                    <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                    <span class="text-2xl font-bold">
                        <span class="text-yellow-600">Guinée</span><span class="text-gray-800">Mall</span>
                    </span>
                </a>

                {{-- Search Bar --}}
                <div class="hidden md:flex flex-1 max-w-md md:mx-8">
                    <form action="{{ route('client.catalog.index') }}" method="GET" class="w-full">
                        <div class="relative">
                            <input type="search" 
                                   name="search"
                                   placeholder="Rechercher un produit..." 
                                   class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-yellow-500">
                            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </form>
                </div>

                {{-- Navigation droite --}}
                <div class="flex flex-wrap items-center gap-3 md:gap-4">
                    {{-- Icône panier --}}
                    @livewire('cart-counter')

                    {{-- Messages et Notifications --}}
                    <div class="flex items-center space-x-2">
                        <!-- Notifications de chat -->
                        @livewire('chat-notification')
                        
                        <!-- Messages -->
                        <a href="{{ route('chat.index') }}" 
                           class="flex items-center space-x-1 text-gray-600 hover:text-yellow-600 transition-colors p-2">
                            <i class="fas fa-comments"></i>
                            <span class="hidden md:inline">Messages</span>
                        </a>
                    </div>

                    {{-- Bouton profil --}}
                    @auth
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center space-x-2 bg-yellow-600 text-white px-4 py-2 rounded-full hover:bg-yellow-700 transition">
                                <i class="fas fa-user"></i>
                                <span>Mon Compte</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" 
                                 @click.away="open = false"
                                 x-cloak
                                 x-transition
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50">
                                <a href="{{ route('client.profile.index') }}" class="block px-4 py-2 hover:bg-gray-100">Mon Profil</a>
                                <a href="{{ route('client.orders.index') }}" class="block px-4 py-2 hover:bg-gray-100">Mes Commandes</a>
                                <a href="{{ route('client.favorites.index') }}" class="block px-4 py-2 hover:bg-gray-100">Mes Favoris</a>
                                <a href="{{ route('client.cart.index') }}" class="block px-4 py-2 hover:bg-gray-100">Mon Panier</a>
                                <a href="{{ route('chat.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    <i class="fas fa-comments mr-2"></i>Messages
                                </a>
                                <hr class="my-2">
                                <a href="javascript:void(0)" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                                   class="block w-full text-left px-4 py-2 hover:bg-gray-100 text-red-600">
                                    <i class="fas fa-sign-out-alt mr-2"></i>
                                    Déconnexion
                                </a>
                            </div>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="flex items-center space-x-2 bg-yellow-600 text-white px-4 py-2 rounded-full hover:bg-yellow-700 transition">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Connexion</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- Contenu principal --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @if(request()->route()->getName() === 'home')
    <footer class="bg-gray-800 text-white py-12 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h4 class="text-xl font-bold mb-4">GuinéeMall</h4>
                    <p class="text-gray-400">Le plus grand marché en ligne de Guinée, connectant vendeurs et acheteurs.</p>
                </div>
                <div>
                    <h5 class="font-semibold mb-4">Liens Utiles</h5>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('client.pages.about') }}" class="hover:text-white">À Propos</a></li>
                        <li><a href="{{ route('client.pages.how') }}" class="hover:text-white">Comment ça marche</a></li>
                        <li><a href="{{ route('client.pages.become-vendor') }}" class="hover:text-white">Devenir Vendeur</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-4">Support</h5>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('client.pages.contact') }}" class="hover:text-white">Contact</a></li>
                        <li><a href="{{ route('client.pages.faq') }}" class="hover:text-white">FAQ</a></li>
                        <li><a href="{{ route('client.pages.delivery') }}" class="hover:text-white">Livraison</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-semibold mb-4">Suivez-nous</h5>
                    <div class="flex space-x-4">
                        <a href="{{ route('client.pages.contact') }}" class="text-gray-400 hover:text-white" aria-label="Facebook"><i class="fab fa-facebook text-xl"></i></a>
                        <a href="{{ route('client.pages.contact') }}" class="text-gray-400 hover:text-white" aria-label="Instagram"><i class="fab fa-instagram text-xl"></i></a>
                        <a href="{{ route('client.pages.contact') }}" class="text-gray-400 hover:text-white" aria-label="WhatsApp"><i class="fab fa-whatsapp text-xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 GuinéeMall. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    @endif
    @livewireScripts
    @livewireScriptConfig
    @stack('scripts')
</body>
</html>