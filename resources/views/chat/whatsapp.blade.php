@extends('layouts.app')

@section('title', 'Messages - GuineeMall')

@push('styles')
<style>
.chat-layout {
    height: calc(100vh - 64px);
    display: flex;
}

.chat-sidebar {
    width: 350px;
    border-right: 1px solid #e5e7eb;
    background: #f8f9fa;
    overflow-y: auto;
}

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}

.chat-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
}

.chat-footer {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    background: #ffffff;
}

.conversation-item {
    padding: 12px 16px;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 12px;
}

.conversation-item:hover {
    background: #e9ecef;
}

.conversation-item.active {
    background: #007bff;
    color: white;
}

.conversation-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-name {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
}

.conversation-item.active .conversation-name {
    color: white;
}

.conversation-last-message {
    font-size: 13px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-item.active .conversation-last-message {
    color: rgba(255, 255, 255, 0.8);
}

.conversation-time {
    font-size: 11px;
    color: #999;
    margin-left: 8px;
}

.conversation-item.active .conversation-time {
    color: rgba(255, 255, 255, 0.7);
}

.unread-badge {
    background: #dc3545;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: bold;
    margin-left: 8px;
}

.message-bubble {
    max-width: 70%;
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
}

.message-sent {
    align-items: flex-end;
}

.message-received {
    align-items: flex-start;
}

.bubble {
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
    position: relative;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.bubble-sent {
    background: #dcf8c6;
    color: #303030;
    border-bottom-right-radius: 4px;
}

.bubble-received {
    background: #ffffff;
    color: #303030;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 11px;
    color: #999;
    margin-top: 4px;
    padding: 0 4px;
}

.message-input-container {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.message-input {
    flex: 1;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    padding: 12px 20px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s ease;
}

.message-input:focus {
    border-color: #007bff;
}

.send-button {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s ease;
}

.send-button:hover {
    background: #0056b3;
}

.send-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
    .chat-sidebar {
        width: 100%;
        position: absolute;
        z-index: 10;
        height: 100%;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .chat-sidebar.open {
        transform: translateX(0);
    }
    
    .chat-main {
        width: 100%;
    }
    
    .conversation-item {
        padding: 16px 20px;
    }
    
    .message-bubble {
        max-width: 85%;
    }
}
</style>
@endpush

@section('content')
<div class="chat-layout" x-data="chatApp()">
    <!-- Sidebar Conversations -->
    <div class="chat-sidebar" :class="{ 'open': sidebarOpen }">
        <!-- Search -->
        <div class="p-3 border-bottom border-gray-200">
            <div class="relative">
                <input 
                    type="text" 
                    x-model="searchQuery"
                    @input="searchConversations"
                    placeholder="Rechercher une conversation..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>
        </div>
        
        <!-- Conversations List -->
        <div class="conversations-list">
            <template x-for="conversation in conversations" :key="conversation.id">
                <div 
                    class="conversation-item"
                    :class="{ 'active': conversation.id === activeConversationId }"
                    @click="openConversation(conversation.id)"
                >
                    <div class="conversation-avatar">
                        <template x-if="conversation.avatar">
                            <img :src="conversation.avatar" :alt="conversation.name" class="w-full h-full rounded-full object-cover">
                        </template>
                        <template x-if="!conversation.avatar">
                            <span x-text="conversation.name.charAt(0).toUpperCase()"></span>
                        </template>
                    </div>
                    
                    <div class="conversation-info">
                        <div class="conversation-name" x-text="conversation.name"></div>
                        <div class="flex items-center">
                            <div class="conversation-last-message" x-text="conversation.last_message"></div>
                            <div class="conversation-time" x-text="conversation.time"></div>
                            <div class="unread-badge" x-show="conversation.unread_count > 0" x-text="conversation.unread_count"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-main">
        <!-- Chat Header -->
        <div class="chat-header" x-show="activeConversation">
            <div class="flex items-center">
                <button @click="toggleSidebar" class="md:hidden mr-3 p-2 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-arrow-left"></i>
                </button>
                
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                        <span x-text="activeConversation?.name?.charAt(0)?.toUpperCase()"></span>
                    </div>
                    <div>
                        <div class="font-semibold" x-text="activeConversation?.name"></div>
                        <div class="text-sm text-gray-500">
                            <template x-if="activeConversation?.role === 'vendeur'">
                                <i class="fas fa-store mr-1"></i>
                                Vendeur
                                <template x-if="activeConversation?.shop_name">
                                    • <span x-text="activeConversation.shop_name"></span>
                                </template>
                            </template>
                            <template x-if="activeConversation?.role === 'client'">
                                <i class="fas fa-user mr-1"></i>
                                Client
                            </template>
                            <template x-if="activeConversation?.role === 'admin'">
                                <i class="fas fa-shield-alt mr-1"></i>
                                Administrateur
                            </template>
                            <span class="ml-2 text-green-500">• En ligne</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="deleteConversation" class="p-2 hover:bg-gray-100 rounded-lg text-red-500" title="Supprimer la conversation">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="chat-body" id="messages-container" x-show="activeConversation">
            <template x-for="message in messages" :key="message.id">
                <div 
                    class="message-bubble"
                    :class="{ 'message-sent': message.sender_id === currentUserId, 'message-received': message.sender_id !== currentUserId }"
                >
                    <div 
                        class="bubble"
                        :class="{ 'bubble-sent': message.sender_id === currentUserId, 'bubble-received': message.sender_id !== currentUserId }"
                    >
                        <div x-text="message.content"></div>
                        
                        <!-- Product/Order Info -->
                        <template x-if="message.product">
                            <div class="mt-2 p-2 bg-gray-100 rounded-lg text-sm">
                                <i class="fas fa-box mr-1"></i>
                                <span x-text="message.product.name"></span>
                            </div>
                        </template>
                        
                        <template x-if="message.order">
                            <div class="mt-2 p-2 bg-blue-100 rounded-lg text-sm">
                                <i class="fas fa-shopping-cart mr-1"></i>
                                Commande #<span x-text="message.order.id"></span>
                            </div>
                        </template>
                    </div>
                    
                    <div class="message-time">
                        <span x-text="formatTime(message.created_at)"></span>
                        <template x-if="message.sender_id === currentUserId">
                            <template x-if="message.is_read">
                                <i class="fas fa-check-double text-blue-500 ml-1"></i>
                            </template>
                            <template x-if="!message.is_read">
                                <i class="fas fa-check text-gray-400 ml-1"></i>
                            </template>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div class="chat-body flex items-center justify-center" x-show="!activeConversation">
            <div class="text-center">
                <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Sélectionnez une conversation</h3>
                <p class="text-gray-500">Choisissez une conversation dans la liste pour commencer à discuter</p>
            </div>
        </div>

        <!-- Message Input -->
        <div class="chat-footer" x-show="activeConversation">
            <form @submit.prevent="sendMessage" class="message-input-container">
                <input 
                    type="text" 
                    x-model="newMessage"
                    @keydown.enter.prevent="sendMessage"
                    placeholder="Tapez votre message..." 
                    class="message-input"
                    :disabled="sending"
                />
                <button 
                    type="submit" 
                    class="send-button"
                    :disabled="!newMessage.trim() || sending"
                >
                    <template x-if="!sending">
                        <i class="fas fa-paper-plane"></i>
                    </template>
                    <template x-if="sending">
                        <i class="fas fa-spinner fa-spin"></i>
                    </template>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function chatApp() {
    return {
        conversations: [],
        messages: [],
        activeConversation: null,
        activeConversationId: null,
        currentUserId: {{ auth()->id() }},
        newMessage: '',
        searchQuery: '',
        sending: false,
        sidebarOpen: false,
        
        init() {
            this.loadConversations();
            this.setupEcho();
            
            // Auto-scroll
            this.$watch('messages', () => {
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            });
        },
        
        async loadConversations() {
            try {
                const response = await fetch('/chat/api/unread');
                const data = await response.json();
                
                // Charger les conversations existantes
                const conversationsResponse = await fetch('/chat');
                const conversationsData = await conversationsResponse.json();
                
                this.conversations = conversationsData.conversations.map(conv => ({
                    ...conv,
                    unread_count: data.unread_count || 0
                }));
            } catch (error) {
                console.error('Erreur lors du chargement des conversations:', error);
            }
        },
        
        async openConversation(conversationId) {
            this.activeConversationId = conversationId;
            this.activeConversation = this.conversations.find(c => c.id === conversationId);
            
            try {
                const response = await fetch(`/chat/conversation/${conversationId}`);
                const data = await response.json();
                this.messages = data.messages.data || data.messages;
                
                // Marquer comme lu
                await fetch(`/chat/read/${conversationId}`, { method: 'PATCH' });
                
                // Mobile: fermer sidebar
                if (window.innerWidth < 768) {
                    this.sidebarOpen = false;
                }
            } catch (error) {
                console.error('Erreur lors du chargement de la conversation:', error);
            }
        },
        
        async sendMessage() {
            if (!this.newMessage.trim() || this.sending) return;
            
            this.sending = true;
            
            try {
                const response = await fetch(`/chat/send/${this.activeConversationId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: this.newMessage
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.messages.push(data.message);
                    this.newMessage = '';
                    this.scrollToBottom();
                }
            } catch (error) {
                console.error('Erreur lors de l\'envoi du message:', error);
            } finally {
                this.sending = false;
            }
        },
        
        async deleteConversation() {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) return;
            
            try {
                const response = await fetch(`/chat/delete/${this.activeConversationId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    this.conversations = this.conversations.filter(c => c.id !== this.activeConversationId);
                    this.activeConversation = null;
                    this.activeConversationId = null;
                    this.messages = [];
                }
            } catch (error) {
                console.error('Erreur lors de la suppression:', error);
            }
        },
        
        async searchConversations() {
            if (!this.searchQuery.trim()) {
                this.loadConversations();
                return;
            }
            
            try {
                const response = await fetch(`/chat/search?query=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                this.conversations = data.conversations;
            } catch (error) {
                console.error('Erreur lors de la recherche:', error);
            }
        },
        
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },
        
        formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString('fr-FR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        },
        
        setupEcho() {
            if (typeof Echo !== 'undefined') {
                // Écouter les nouveaux messages
                Echo.private(`chat.${this.currentUserId}`)
                    .listen('MessageSent', (e) => {
                        if (e.message.receiver_id === this.currentUserId) {
                            this.messages.push(e.message);
                            this.scrollToBottom();
                        }
                    });
            }
        }
    };
}
</script>
@endsection
