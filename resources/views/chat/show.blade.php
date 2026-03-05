@extends('layouts.messaging')

@section('title', 'Conversation avec ' . $otherUser->name . ' - GuinéeMall')

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
                    @foreach($conversations as $conv)
                        <a href="{{ route('chat.show', $conv->id) }}" 
                           class="block hover:bg-gray-50 transition-colors {{ $conv->id == $conversation->id ? 'bg-guinea-orange-50 border-l-4 border-guinea-orange-500' : '' }}">
                            <div class="p-4 flex items-start space-x-3">
                                <!-- Avatar -->
                                <div class="flex-shrink-0 relative">
                                    @if($conv->avatar)
                                        <img src="{{ $conv->avatar }}" alt="{{ $conv->name }}" 
                                             class="w-12 h-12 rounded-full object-cover">
                                    @else
                                        <div class="w-12 h-12 bg-gradient-to-br from-guinea-orange-400 to-guinea-yellow-400 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-lg">
                                                {{ strtoupper(substr($conv->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    <!-- Badge messages non lus -->
                                    @if($conv->unread_count > 0)
                                        <span class="absolute -top-1 -right-1 bg-guinea-orange-500 text-white text-xs rounded-full px-2 py-0.5 min-w-[20px] text-center font-bold animate-pulse">
                                            {{ $conv->unread_count }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Infos conversation -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $conv->name }}
                                        </p>
                                        <span class="text-xs text-gray-500">
                                            {{ $conv->last_message_time ?? '' }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs text-gray-500">
                                            @if($conv->role === 'vendeur')
                                                <i class="fas fa-store mr-1"></i>
                                                Vendeur
                                                @if($conv->shop_name)
                                                    • {{ $conv->shop_name }}
                                                @endif
                                            @elseif($conv->role === 'client')
                                                <i class="fas fa-user mr-1"></i>
                                                Client
                                            @else
                                                <i class="fas fa-shield-alt mr-1"></i>
                                                Admin
                                            @endif
                                        </p>
                                        
                                        <!-- Badge non lu séparé -->
                                        @if($conv->unread_count > 0)
                                            <span class="bg-guinea-orange-100 text-guinea-orange-800 text-xs rounded-full px-2 py-0.5 font-medium">
                                                {{ $conv->unread_count }} nouveau{{ $conv->unread_count > 1 ? 'x' : '' }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Dernier message -->
                                    @if($conv->last_message)
                                        <p class="text-sm text-gray-600 truncate mt-1">
                                            {{ $conv->last_message }}
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
                    <p class="text-lg font-medium text-gray-900 mb-2">Aucune conversation</p>
                    <p class="text-sm text-gray-500">Commencez une conversation depuis un produit ou une commande</p>
                </div>
            @endif
        </div>
    </div>

    <!-- COLONNE DROITE (70%) - Zone conversation -->
    <div class="w-[70%] bg-gray-50 flex flex-col">
        
        <!-- Header conversation -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <!-- Avatar -->
                    <div class="relative">
                        @if($otherUser->avatar)
                            <img src="{{ $otherUser->avatar }}" alt="{{ $otherUser->name }}" 
                                 class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 bg-gradient-to-br from-guinea-orange-400 to-guinea-yellow-400 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">
                                    {{ strtoupper(mb_substr($otherUser->name, 0, 1, 'UTF-8')) }}
                                </span>
                            </div>
                        @endif
                        
                        <!-- Statut en ligne -->
                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                    </div>
                    
                    <!-- Infos utilisateur -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $otherUser->name }}</h3>
                        <div class="flex items-center text-sm text-gray-500">
                            @if($otherUser->role === 'vendeur')
                                <i class="fas fa-store mr-1"></i>
                                Vendeur
                                @if($otherUser->vendor && $otherUser->vendor->shop_name)
                                    • {{ $otherUser->vendor->shop_name }}
                                @endif
                            @elseif($otherUser->role === 'client')
                                <i class="fas fa-user mr-1"></i>
                                Client
                            @else
                                <i class="fas fa-shield-alt mr-1"></i>
                                Administrateur
                            @endif
                            <span class="ml-2 text-green-500">• En ligne</span>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <button class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors" title="Plus d'options">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Zone messages scrollable -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-6">
            @if($conversation->messages->count() > 0)
                <div class="space-y-4">
                    @foreach($conversation->messages as $message)
                        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}" 
                             data-message-id="{{ $message->id }}">
                            <div class="max-w-xs lg:max-w-md {{ $message->sender_id === auth()->id() ? 'order-2' : 'order-1' }}">
                                @if($message->sender_id !== auth()->id())
                                    <!-- Message de l'autre personne (aligné à gauche) -->
                                    <div class="flex items-center space-x-2 mb-2">
                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 text-sm font-bold">
                                            {{ strtoupper(mb_substr($message->sender->name, 0, 1, 'UTF-8')) }}
                                        </div>
                                        <span class="text-xs text-gray-500 font-medium">{{ $message->sender->name }}</span>
                                        <span class="text-xs text-gray-400">{{ $message->created_at->format('H:i') }}</span>
                                    </div>
                                    
                                    <!-- Contexte produit/commande -->
                                    @if($message->product)
                                        <div class="bg-guinea-yellow-50 border border-guinea-yellow-200 rounded-lg p-3 mb-2">
                                            <div class="flex items-center space-x-2 text-sm">
                                                <i class="fas fa-box text-guinea-yellow-600"></i>
                                                <span class="text-guinea-yellow-800 font-medium">Produit: {{ $message->product->name }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($message->order)
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-2">
                                            <div class="flex items-center space-x-2 text-sm">
                                                <i class="fas fa-shopping-bag text-blue-600"></i>
                                                <span class="text-blue-800 font-medium">Commande #{{ $message->order->id }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Bulle de message reçu -->
                                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-none p-4 shadow-sm">
                                        <p class="text-gray-800 leading-relaxed">{{ $message->content }}</p>
                                    </div>
                                    
                                    <!-- Indicateur de lecture -->
                                    <div class="flex items-center space-x-1 mt-1 text-xs text-gray-400">
                                        @if($message->is_read)
                                            <i class="fas fa-check-double text-blue-500"></i>
                                            <span>Lu</span>
                                        @else
                                            <i class="fas fa-check text-gray-400"></i>
                                            <span>Envoyé</span>
                                        @endif
                                    </div>
                                @else
                                    <!-- Message de l'utilisateur actuel (aligné à droite) -->
                                    <div class="flex items-center justify-end space-x-2 mb-2">
                                        <span class="text-xs text-gray-400">{{ $message->created_at->format('H:i') }}</span>
                                        <span class="text-xs text-gray-500 font-medium">Moi</span>
                                    </div>
                                    
                                    <!-- Contexte produit/commande -->
                                    @if($message->product)
                                        <div class="bg-guinea-yellow-50 border border-guinea-yellow-200 rounded-lg p-3 mb-2">
                                            <div class="flex items-center space-x-2 text-sm">
                                                <i class="fas fa-box text-guinea-yellow-600"></i>
                                                <span class="text-guinea-yellow-800 font-medium">Produit: {{ $message->product->name }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($message->order)
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-2">
                                            <div class="flex items-center space-x-2 text-sm">
                                                <i class="fas fa-shopping-bag text-blue-600"></i>
                                                <span class="text-blue-800 font-medium">Commande #{{ $message->order->id }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Bulle de message envoyé -->
                                    <div class="bg-gradient-to-r from-guinea-yellow-500 to-guinea-orange-500 text-white rounded-2xl rounded-tr-none p-4 shadow-lg">
                                        <p class="text-white leading-relaxed">{{ $message->content }}</p>
                                    </div>
                                    
                                    <!-- Indicateur de lecture -->
                                    <div class="flex items-center justify-end space-x-1 mt-1 text-xs text-gray-400">
                                        @if($message->is_read)
                                            <i class="fas fa-check-double text-blue-500"></i>
                                            <span>Lu</span>
                                        @else
                                            <i class="fas fa-check text-gray-400"></i>
                                            <span>Envoyé</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Aucun message -->
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-comments text-2xl"></i>
                    </div>
                    <p class="text-lg font-medium">Commencez la conversation</p>
                    <p class="text-sm">Envoyez votre premier message à {{ $otherUser->name }}</p>
                </div>
            @endif
        </div>

        <!-- Input fixe en bas -->
        <div class="bg-white border-t border-gray-200 p-4">
            <form action="{{ route('chat.send', $conversation->id) }}" method="POST" class="flex items-end space-x-3">
                @csrf
                <div class="flex-1">
                    <textarea 
                        name="content"
                        rows="1"
                        placeholder="Tapez votre message..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-guinea-orange-500 focus:border-guinea-orange-500 resize-none transition-all"
                        maxlength="1000"
                        required
                    ></textarea>
                </div>
                
                <!-- Boutons d'action -->
                <div class="flex items-center space-x-2">
                    <button type="button" class="text-gray-400 hover:text-gray-600 p-2 transition-colors" title="Joindre un fichier">
                        <i class="fas fa-paperclip text-lg"></i>
                    </button>
                    
                    <button 
                        type="submit"
                        class="bg-gradient-to-r from-guinea-yellow-500 to-guinea-orange-500 text-white px-6 py-3 rounded-xl font-semibold hover:from-guinea-yellow-600 hover:to-guinea-orange-600 transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-paper-plane"></i>
                        <span>Envoyer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Scroll vers le bas au chargement
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth'
        });
    }
});

// Auto-resize du textarea
document.querySelector('textarea[name="content"]')?.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
</script>
@endsection
