@extends('layouts.messaging')

@section('title', 'Messages - GuinéeMall')

@section('content')
<div class="flex h-[calc(100vh-80px)]">
    
    <!-- COLONNE GAUCHE (30%) - Liste des conversations -->
    <div class="w-[30%] bg-white border-r border-gray-200 flex flex-col">
        <!-- Header liste conversations -->
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Messages</h2>
            
            <!-- Barre de recherche -->
            <div class="relative">
                <input 
                    type="text" 
                    id="search-input"
                    placeholder="Rechercher une conversation..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-guinea-orange-500 focus:border-guinea-orange-500 transition-all text-sm"
                    x-data="{ searchTimeout: null }"
                    x-on:input="
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            searchConversations($el.value);
                        }, 300);
                    "
                >
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
        
        <!-- Liste des conversations scrollable -->
        <div class="flex-1 overflow-y-auto">
            @if(isset($conversations) && $conversations->count() > 0)
                <div class="divide-y divide-gray-100">
                    @foreach($conversations as $conversation)
                        <a href="{{ route('chat.show', $conversation->id) }}" 
                           class="block hover:bg-gray-50 transition-colors">
                            <div class="p-4 flex items-start space-x-3">
                                <!-- Avatar -->
                                <div class="flex-shrink-0 relative">
                                    @if($conversation->avatar)
                                        <img src="{{ $conversation->avatar }}" alt="{{ $conversation->name }}" 
                                             class="w-12 h-12 rounded-full object-cover">
                                    @else
                                        <div class="w-12 h-12 bg-gradient-to-br from-guinea-orange-400 to-guinea-yellow-400 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-lg">
                                                {{ strtoupper(substr($conversation->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <!-- Badge messages non lus -->
                                    @if($conversation->unread_count > 0)
                                        <span class="absolute -top-1 -right-1 bg-guinea-orange-500 text-white text-xs rounded-full px-2 py-0.5 min-w-[20px] text-center font-bold animate-pulse">
                                            {{ $conversation->unread_count }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Infos conversation -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $conversation->name }}
                                        </p>
                                        <span class="text-xs text-gray-500">
                                            {{ $conversation->last_message_time ?? '' }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs text-gray-500">
                                            @if($conversation->role === 'vendeur')
                                                <i class="fas fa-store mr-1"></i>
                                                Vendeur
                                                @if($conversation->shop_name)
                                                    • {{ $conversation->shop_name }}
                                                @endif
                                            @elseif($conversation->role === 'client')
                                                <i class="fas fa-user mr-1"></i>
                                                Client
                                            @else
                                                <i class="fas fa-shield-alt mr-1"></i>
                                                Admin
                                            @endif
                                        </p>
                                        
                                        <!-- Badge non lu séparé -->
                                        @if($conversation->unread_count > 0)
                                            <span class="bg-guinea-orange-100 text-guinea-orange-800 text-xs rounded-full px-2 py-0.5 font-medium">
                                                {{ $conversation->unread_count }} nouveau{{ $conversation->unread_count > 1 ? 'x' : '' }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Dernier message -->
                                    @if($conversation->last_message)
                                        <p class="text-sm text-gray-600 truncate mt-1">
                                            {{ $conversation->last_message }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <!-- Aucune conversation -->
                <div class="flex flex-col items-center justify-center h-full text-center p-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-comments text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-lg font-medium text-gray-900 mb-2">Aucune conversation pour le moment</p>
                    <p class="text-sm text-gray-500">
                        @if(auth()->user()->role === 'client')
                            Commencez une conversation depuis un produit ou une commande
                        @elseif(auth()->user()->role === 'vendeur')
                            Attendez que les clients vous contactent
                        @else
                            Aucune conversation active dans le système
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- COLONNE DROITE (70%) - Zone conversation -->
    <div class="w-[70%] bg-gray-50 flex flex-col">
        <!-- Placeholder quand aucune conversation sélectionnée -->
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-comments text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Sélectionnez une conversation</h3>
                <p class="text-sm text-gray-500">Choisissez une conversation dans la liste pour commencer à discuter</p>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction de recherche avec debounce
function searchConversations(query) {
    const conversations = document.querySelectorAll('a[href*="/chat/"]');
    
    conversations.forEach(conversation => {
        const name = conversation.querySelector('.font-semibold')?.textContent.toLowerCase() || '';
        const shopName = conversation.querySelector('.fa-store')?.parentElement?.textContent.toLowerCase() || '';
        const searchText = query.toLowerCase();
        
        if (name.includes(searchText) || shopName.includes(searchText)) {
            conversation.style.display = 'block';
        } else {
            conversation.style.display = 'none';
        }
    });
}

// Focus initial sur la recherche
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search-input')?.focus();
});
</script>
@endsection
