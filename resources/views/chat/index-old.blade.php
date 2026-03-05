@extends('layouts.app')

@section('title', 'Messages - GuinéeMall')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header amélioré -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-comments text-white"></i>
                        </div>
                        Messages
                    </h1>
                    <div class="ml-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            @if(auth()->user()->role === 'client')
                                💬 Espace client
                            @else
                                🏪 Espace vendeur
                            @endif
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Bouton Trouver un interlocuteur -->
                    <a href="{{ route('chat.find') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-search mr-2"></i>
                        Trouver un interlocuteur
                    </a>

                    <!-- Compteur de messages non lus -->
                    @if($unreadCount > 0)
                        <div class="flex items-center">
                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-semibold animate-pulse">
                                {{ $unreadCount }} non lu(s)
                            </span>
                        </div>
                    @endif

                    <!-- Recherche rapide -->
                    <div class="relative">
                        <input type="text" 
                               id="search-conversations" 
                               placeholder="Rechercher une conversation..." 
                               class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if($conversations->count() > 0)
            <!-- Grille des conversations -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($conversations as $conversation)
                    <?php
                    $otherUser = $conversation->sender_id === auth()->id() ? $conversation->receiver : $conversation->sender;
                    $lastMessage = Message::between(auth()->id(), $otherUser->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $unreadCount = Message::where('sender_id', $otherUser->id)
                        ->where('receiver_id', auth()->id())
                        ->where('is_read', false)
                        ->count();
                    ?>
                    
                    <a href="{{ route('chat.show', $otherUser) }}" 
                       class="block bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group">
                        <!-- Header de la carte -->
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="relative">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                                            {{ strtoupper(mb_substr($otherUser->name, 0, 1, 'UTF-8')) }}
                                        </div>
                                        @if($unreadCount > 0)
                                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold animate-pulse">
                                                {{ $unreadCount }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                                            {{ $otherUser->name }}
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            @if($otherUser->role === 'vendeur')
                                                🏪 Vendeur
                                            @else
                                                👤 Client
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $lastMessage->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>

                        <!-- Dernier message -->
                        <div class="p-6">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    @if($lastMessage->sender_id === auth()->id())
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600 text-xs"></i>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-gray-600 text-xs"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-600 truncate {{ $unreadCount > 0 ? 'font-semibold' : '' }}">
                                        {{ $lastMessage->content }}
                                    </p>
                                    
                                    <!-- Contexte du message -->
                                    @if($lastMessage->product)
                                        <div class="mt-2 flex items-center text-xs text-blue-600">
                                            <i class="fas fa-box mr-1"></i>
                                            <span class="truncate">{{ $lastMessage->product->name }}</span>
                                        </div>
                                    @endif

                                    @if($lastMessage->order)
                                        <div class="mt-2 flex items-center text-xs text-yellow-600">
                                            <i class="fas fa-shopping-bag mr-1"></i>
                                            Commande #{{ $lastMessage->order->id }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Footer avec actions -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    {{ Message::between(auth()->id(), $otherUser->id)->count() }} message(s)
                                </span>
                                <div class="flex items-center text-blue-600 group-hover:text-blue-700 transition-colors">
                                    <span class="text-sm font-medium">Ouvrir</span>
                                    <i class="fas fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <!-- État vide amélioré -->
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
                    <i class="fas fa-comments text-3xl text-gray-400"></i>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    Aucune conversation
                </h2>
                
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Commencez une conversation depuis un produit ou une commande pour discuter avec des @if(auth()->user()->role === 'client') vendeurs @else clients @endif
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('chat.find') }}" 
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-search mr-2"></i>
                        Trouver un interlocuteur
                    </a>
                    
                    @if(auth()->user()->role === 'client')
                        <a href="{{ route('client.catalog.index') }}" 
                           class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Parcourir les produits
                        </a>
                    @else
                        <a href="{{ route('vendeur.dashboard') }}" 
                           class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-store mr-2"></i>
                            Voir mon tableau de bord
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes au survol
    const cards = document.querySelectorAll('.group');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Recherche de conversations
    const searchInput = document.getElementById('search-conversations');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.group');
            
            cards.forEach(card => {
                const name = card.querySelector('h3')?.textContent.toLowerCase() || '';
                const message = card.querySelector('.text-gray-600')?.textContent.toLowerCase() || '';
                
                if (name.includes(query) || message.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endsection
