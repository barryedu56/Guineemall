@extends('layouts.app')

@section('title', 'Conversation avec ' . $user->name . ' - GuinéeMall')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header de conversation -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('chat.index') }}" class="text-white/80 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-full flex items-center justify-center text-white font-semibold">
                        {{ strtoupper(mb_substr($user->name, 0, 1, 'UTF-8')) }}
                    </div>
                    <div>
                        <h2 class="font-semibold">{{ $user->name }}</h2>
                        <p class="text-sm text-blue-100">
                            @if($user->role === 'vendeur')
                                Vendeur
                            @elseif($user->role === 'client')
                                Client
                            @else
                                Administrateur
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="deleteConversation()" class="text-white/80 hover:text-white transition-colors p-2">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div id="messages-container" class="h-[500px] overflow-y-auto p-4 space-y-4 bg-gray-50">
            @if($messages->count() > 0)
                @foreach($messages as $message)
                    <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}" 
                         data-message-id="{{ $message->id }}">
                        <div class="max-w-xs lg:max-w-md">
                            @if($message->sender_id !== auth()->id())
                                <div class="flex items-center space-x-2 mb-1">
                                    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs font-semibold text-gray-600">
                                        {{ strtoupper(mb_substr($message->sender->name, 0, 1, 'UTF-8')) }}
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $message->sender->name }}</span>
                                </div>
                            @endif
                            
                            <div class="{{ $message->sender_id === auth()->id() 
                                ? 'bg-blue-600 text-white rounded-l-2xl rounded-tr-2xl' 
                                : 'bg-white text-gray-800 rounded-r-2xl rounded-tl-2xl border border-gray-200' 
                            }} px-4 py-2 shadow-sm">
                                @if($message->type === 'text')
                                    <p class="text-sm">{{ $message->content }}</p>
                                @elseif($message->type === 'image')
                                    <img src="{{ $message->content }}" alt="Image" class="rounded-lg max-w-full">
                                @endif
                                
                                @if($message->product)
                                    <div class="mt-2 p-2 bg-black/10 rounded-lg">
                                        <p class="text-xs font-medium mb-1">
                                            <i class="fas fa-box mr-1"></i>
                                            {{ $message->product->name }}
                                        </p>
                                        <a href="{{ route('client.catalog.show', $message->product) }}" 
                                           class="text-xs underline hover:no-underline">
                                            Voir le produit
                                        </a>
                                    </div>
                                @endif
                                
                                @if($message->order)
                                    <div class="mt-2 p-2 bg-black/10 rounded-lg">
                                        <p class="text-xs font-medium mb-1">
                                            <i class="fas fa-shopping-bag mr-1"></i>
                                            Commande #{{ $message->order->id }}
                                        </p>
                                        <a href="{{ route('client.orders.show', $message->order) }}" 
                                           class="text-xs underline hover:no-underline">
                                            Voir la commande
                                        </a>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex items-center {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }} mt-1">
                                <span class="text-xs text-gray-500">
                                    {{ $message->created_at->format('H:i') }}
                                    @if($message->sender_id === auth()->id())
                                        @if($message->is_read)
                                            <i class="fas fa-check-double text-blue-500 ml-1"></i>
                                        @else
                                            <i class="fas fa-check text-gray-400 ml-1"></i>
                                        @endif
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="flex items-center justify-center h-full text-gray-400">
                    <div class="text-center">
                        <i class="fas fa-comment-dots text-4xl mb-2"></i>
                        <p class="text-sm">Aucun message dans cette conversation</p>
                        <p class="text-xs mt-1">Soyez le premier à envoyer un message</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Formulaire d'envoi -->
        <div class="border-t border-gray-200 p-4 bg-white">
            <form id="message-form" class="flex items-end space-x-2">
                @csrf
                <input type="hidden" name="receiver_id" value="{{ $user->id }}">
                
                <div class="flex-1">
                    <textarea 
                        name="content" 
                        id="message-input"
                        rows="1"
                        placeholder="Tapez votre message..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        maxlength="1000"
                    ></textarea>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button type="button" 
                            onclick="attachFile()"
                            class="p-2 text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button type="button" 
                            onclick="attachImage()"
                            class="p-2 text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-image"></i>
                    </button>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center space-x-2">
                        <i class="fas fa-paper-plane"></i>
                        <span>Envoyer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Input caché pour les fichiers -->
<input type="file" id="file-input" accept="image/*,.pdf,.doc,.docx" style="display: none;">

<script>
let currentUserId = {{ auth()->id() }};
let receiverId = {{ $user->id }};

// Auto-scroll vers le bas
function scrollToBottom() {
    const container = document.getElementById('messages-container');
    container.scrollTop = container.scrollHeight;
}

// Charger les nouveaux messages périodiquement
function loadNewMessages() {
    fetch(`/api/messages/unread`)
        .then(response => response.json())
        .then(data => {
            if (data.unread_count > 0) {
                // Recharger la page pour voir les nouveaux messages
                window.location.reload();
            }
        });
}

// Envoyer un message
document.getElementById('message-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const input = document.getElementById('message-input');
    
    if (!input.value.trim()) return;
    
    fetch(`/chat/send/${receiverId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            // Ajouter le message à l'interface
            addMessageToUI(data.message);
            scrollToBottom();
        } else {
            alert(data.message || 'Erreur lors de l\'envoi du message');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de l\'envoi du message');
    });
});

// Ajouter un message à l'interface
function addMessageToUI(message) {
    const container = document.getElementById('messages-container');
    const messageHtml = `
        <div class="flex justify-end" data-message-id="${message.id}">
            <div class="max-w-xs lg:max-w-md">
                <div class="bg-blue-600 text-white rounded-l-2xl rounded-tr-2xl px-4 py-2 shadow-sm">
                    <p class="text-sm">${message.content}</p>
                </div>
                <div class="flex items-center justify-end mt-1">
                    <span class="text-xs text-gray-500">
                        ${new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}
                        <i class="fas fa-check text-gray-400 ml-1"></i>
                    </span>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', messageHtml);
}

// Joindre un fichier
function attachFile() {
    document.getElementById('file-input').click();
}

// Joindre une image
function attachImage() {
    const input = document.getElementById('file-input');
    input.accept = 'image/*';
    input.click();
}

// Gérer le sélection de fichier
document.getElementById('file-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Ici vous pourriez implémenter l'upload de fichier
        console.log('Fichier sélectionné:', file);
    }
});

// Supprimer la conversation
function deleteConversation() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) {
        fetch(`/chat/delete/${receiverId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/chat';
            } else {
                alert(data.message || 'Erreur lors de la suppression');
            }
        });
    }
}

// Auto-resize du textarea
document.getElementById('message-input').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    
    // Vérifier les nouveaux messages toutes les 5 secondes
    setInterval(loadNewMessages, 5000);
});
</script>
@endsection
