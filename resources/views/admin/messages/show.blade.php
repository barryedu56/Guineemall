@extends('admin.layouts.app')

@section('title', 'Conversation avec ' . $user->name . ' - GuinéeMall')

@push('styles')
<style>
/* Animations personnalisées */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

.animate-slide-in {
    animation: slideIn 0.3s ease-out;
}

.animate-pulse {
    animation: pulse 2s infinite;
}

.scroll-to-bottom {
    scroll-margin-bottom: 100px;
}

/* Responsive mobile */
@media (max-width: 768px) {
    .max-w-xs {
        max-width: 200px;
    }
    
    .max-w-md {
        max-width: 250px;
    }
    
    .space-y-4 > div {
        margin-bottom: 0.75rem;
    }
}
</style>
@endpush

@section('content')
<div class="slide-in h-[calc(100vh-8rem)]">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-full flex flex-col">
        <!-- Header fixe de conversation -->
        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.messages.index') }}" class="text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-lg">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </a>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-white font-bold">
                            {{ strtoupper(mb_substr($user->name, 0, 1, 'UTF-8')) }}
                        </div>
                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                    </div>
                    <div>
                        <h2 class="font-bold text-lg">{{ $user->name }}</h2>
                        <p class="text-sm text-yellow-100 flex items-center">
                            @if($user->role === 'vendeur')
                                <i class="fas fa-store mr-1"></i>
                                Vendeur
                                @if($user->vendor && $user->vendor->shop_name)
                                    • {{ $user->vendor->shop_name }}
                                @endif
                            @elseif($user->role === 'client')
                                <i class="fas fa-user mr-1"></i>
                                Client
                            @else
                                <i class="fas fa-shield-alt mr-1"></i>
                                Administrateur
                            @endif
                            <span class="ml-2 text-green-200">• En ligne</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="deleteConversation()" class="text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-lg" title="Supprimer la conversation">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Zone des messages (scrollable) -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-4 bg-gray-50" x-data="{ autoScroll: true }" x-init="$watch('autoScroll', () => scrollToBottom())">
            @if($messages->count() > 0)
                <div class="space-y-4">
                    @foreach($messages as $message)
                        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }} {{ $loop->last ? 'scroll-to-bottom' : '' }}" 
                             data-message-id="{{ $message->id }}"
                             x-data="{ showMessage: false }"
                             x-init="setTimeout(() => { showMessage = true }, 50)">
                            <div class="max-w-xs lg:max-w-md {{ $message->sender_id === auth()->id() ? 'order-2' : 'order-1' }} {{ showMessage ? 'animate-fade-in' : 'opacity-0' }}">
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
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-2">
                                            <div class="flex items-center space-x-2 text-sm">
                                                <i class="fas fa-box text-yellow-600"></i>
                                                <span class="text-yellow-800 font-medium">Produit: {{ $message->product->name }}</span>
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
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-2">
                                            <div class="flex items-center space-x-2 text-sm">
                                                <i class="fas fa-box text-yellow-600"></i>
                                                <span class="text-yellow-800 font-medium">Produit: {{ $message->product->name }}</span>
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
                                    <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-2xl rounded-tr-none p-4 shadow-lg">
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
                    <p class="text-sm">Envoyez votre premier message à {{ $user->name }}</p>
                </div>
            @endif
        </div>

        <!-- Input fixe en bas -->
        <div class="border-t border-gray-200 bg-white p-4">
            <form id="message-form" class="flex items-end space-x-3">
                @csrf
                <div class="flex-1">
                    <textarea 
                        id="message-input"
                        name="content"
                        rows="1"
                        placeholder="Tapez votre message..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none transition-all"
                        maxlength="1000"
                        x-data="{ rows: 1 }"
                        x-input="rows = $el.scrollHeight > 32 ? 2 : 1; $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                        ></textarea>
                </div>
                
                <!-- Boutons d'action -->
                <div class="flex items-center space-x-2">
                    <button type="button" class="text-gray-400 hover:text-gray-600 p-2 transition-colors" title="Joindre un fichier">
                        <i class="fas fa-paperclip text-lg"></i>
                    </button>
                    
                    <button 
                        type="submit"
                        id="send-button"
                        class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-6 py-3 rounded-xl font-semibold hover:from-yellow-600 hover:to-orange-600 transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-paper-plane"></i>
                        <span>Envoyer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Variables globales
const conversationUserId = {{ $user->id }};
const currentUserId = {{ auth()->id() }};

// Fonctions utilitaires
function scrollToBottom() {
    const container = document.getElementById('messages-container');
    if (container) {
        // Scroll smooth vers le bas
        container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth'
        });
    }
}

function formatTime(date) {
    return new Date(date).toLocaleTimeString('fr-FR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function addMessageToChat(message, isSent = false) {
    const container = document.getElementById('messages-container');
    const messageHtml = `
        <div class="flex ${isSent ? 'justify-end' : 'justify-start'} animate-fade-in" data-message-id="${message.id}">
            <div class="max-w-xs lg:max-w-md ${isSent ? 'order-2' : 'order-1'}">
                ${!isSent ? `
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 text-sm font-bold">
                            ${message.sender.name.charAt(0).toUpperCase()}
                        </div>
                        <span class="text-xs text-gray-500 font-medium">${message.sender.name}</span>
                        <span class="text-xs text-gray-400">${formatTime(message.created_at)}</span>
                    </div>
                ` : `
                    <div class="flex items-center justify-end space-x-2 mb-2">
                        <span class="text-xs text-gray-400">${formatTime(message.created_at)}</span>
                        <span class="text-xs text-gray-500 font-medium">Moi</span>
                    </div>
                `}
                
                ${message.product ? `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-2">
                        <div class="flex items-center space-x-2 text-sm">
                            <i class="fas fa-box text-yellow-600"></i>
                            <span class="text-yellow-800 font-medium">Produit: ${message.product.name}</span>
                        </div>
                    </div>
                ` : ''}
                
                ${message.order ? `
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-2">
                        <div class="flex items-center space-x-2 text-sm">
                            <i class="fas fa-shopping-bag text-blue-600"></i>
                            <span class="text-blue-800 font-medium">Commande #${message.order.id}</span>
                        </div>
                    </div>
                ` : ''}
                
                <div class="${isSent ? 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-2xl rounded-tr-none' : 'bg-white border border-gray-200 rounded-2xl rounded-tl-none'} p-4 ${isSent ? 'shadow-lg' : 'shadow-sm'}">
                    <p class="${isSent ? 'text-white' : 'text-gray-800'} leading-relaxed">${message.content}</p>
                </div>
                
                <div class="flex ${isSent ? 'justify-end' : 'justify-start'} space-x-1 mt-1 text-xs text-gray-400">
                    ${message.is_read ? '<i class="fas fa-check-double text-blue-500"></i><span>Lu</span>' : '<i class="fas fa-check text-gray-400"></i><span>Envoyé</span>'}
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', messageHtml);
    
    // Scroll vers le bas après ajout
    setTimeout(() => scrollToBottom(), 100);
}

// Envoi de message
document.getElementById('message-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const input = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const content = input.value.trim();
    
    if (!content) return;
    
    // Désactiver le bouton et montrer le loading
    sendButton.disabled = true;
    sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Envoi...</span>';
    
    try {
        const response = await fetch(`{{ route('admin.messages.send', $user->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ content: content })
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                // Ajouter le message à la conversation avec animation
                addMessageToChat(data.message, true);
                
                // Vider le champ
                input.value = '';
                input.style.height = 'auto';
                
                // Réactiver le bouton
                sendButton.disabled = false;
                sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Envoyer</span>';
            }
        }
    } catch (error) {
        console.error('Erreur:', error);
        // Réactiver le bouton en cas d'erreur
        sendButton.disabled = false;
        sendButton.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Envoyer</span>';
    }
});

// Support pour la touche Entrée
document.getElementById('message-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('message-form').dispatchEvent(new Event('submit'));
    }
});

// Auto-resize du textarea
document.getElementById('message-input').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Supprimer la conversation
async function deleteConversation() {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette conversation ? Cette action est irréversible.')) {
        return;
    }
    
    try {
        const response = await fetch(`{{ route('admin.messages.delete', $user->id) }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.ok) {
            // Animation de sortie
            document.body.style.opacity = '0';
            setTimeout(() => {
                window.location.href = '{{ route('admin.messages.index') }}';
            }, 300);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la suppression de la conversation.');
    }
}

// Scroll initial vers le bas avec animation
document.addEventListener('DOMContentLoaded', function() {
    // Attendre que les images soient chargées
    setTimeout(() => {
        scrollToBottom();
        
        // Marquer les messages comme lus après un délai
        setTimeout(() => {
            const unreadMessages = document.querySelectorAll('[data-message-id]:not(.read)');
            unreadMessages.forEach(msg => {
                msg.classList.add('read');
            });
        }, 1000);
    }, 500);
});

// Écoute des messages en temps réel (si disponible)
if (typeof Echo !== 'undefined') {
    Echo.private(`conversation.${conversationUserId}`)
        .listen('MessageSent', (e) => {
            if (e.message.sender_id !== currentUserId) {
                addMessageToChat(e.message, false);
                
                // Jouer une notification sonore si l'onglet n'est pas actif
                if (document.hidden) {
                    // Notification navigateur
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification('Nouveau message', {
                            body: `${e.message.sender.name}: ${e.message.content}`,
                            icon: '/favicon.ico'
                        });
                    }
                }
            }
        });
}

// Gestion du focus sur la fenêtre
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Marquer les messages comme lus quand l'onglet devient actif
        const unreadMessages = document.querySelectorAll('[data-message-id]:not(.read)');
        unreadMessages.forEach(msg => {
            msg.classList.add('read');
        });
    }
});
</script>
@endsection
