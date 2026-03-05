<div>
    <!-- Bouton de notification avec badge -->
    <div class="relative">
        <button wire:click="$toggle('showNotifications')" 
                class="relative p-2 text-gray-600 hover:text-gray-900 transition-colors">
            <i class="fas fa-bell text-xl"></i>
            @if($unreadCount > 0)
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-semibold">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <!-- Panneau de notifications -->
        @if($showNotifications)
            <div class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                <!-- Header -->
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Messages non lus</h3>
                        @if($unreadCount > 0)
                            <button wire:click="markAllAsRead" 
                                    class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                                Tout marquer comme lu
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Liste des notifications -->
                <div class="max-h-96 overflow-y-auto">
                    @if($unreadMessages->count() > 0)
                        @foreach($unreadMessages as $message)
                            <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                <div class="flex items-start space-x-3">
                                    <!-- Avatar de l'expéditeur -->
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">
                                            {{ strtoupper(mb_substr($message->sender->name, 0, 1, 'UTF-8')) }}
                                        </div>
                                    </div>

                                    <!-- Contenu du message -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $message->sender->name }}
                                            </p>
                                            <button wire:click="markAsRead({{ $message->id }})" 
                                                    class="text-gray-400 hover:text-gray-600 transition-colors">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 truncate">
                                            {{ $message->content }}
                                        </p>

                                        <!-- Contexte (produit ou commande) -->
                                        @if($message->product)
                                            <p class="text-xs text-blue-600 mt-1">
                                                <i class="fas fa-box mr-1"></i>
                                                {{ $message->product->name }}
                                            </p>
                                        @endif

                                        @if($message->order)
                                            <p class="text-xs text-yellow-600 mt-1">
                                                <i class="fas fa-shopping-bag mr-1"></i>
                                                Commande #{{ $message->order->id }}
                                            </p>
                                        @endif

                                        <!-- Actions -->
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs text-gray-500">
                                                {{ $message->created_at->diffForHumans() }}
                                            </span>
                                            <button wire:click="openChat({{ $message->sender->id }})" 
                                                    class="text-xs text-blue-600 hover:text-blue-800 transition-colors">
                                                <i class="fas fa-reply mr-1"></i>
                                                Répondre
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-check-circle text-2xl mb-2"></i>
                            <p class="text-sm">Aucun message non lu</p>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-2 border-t border-gray-200">
                    <a href="{{ route('chat.index') }}" 
                       class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-comments mr-1"></i>
                        Voir tous les messages
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Script pour fermer le panneau en cliquant à l'extérieur -->
    <script>
        document.addEventListener('click', function(event) {
            const notificationButton = event.target.closest('button[wire\\:click*="showNotifications"]');
            const notificationPanel = event.target.closest('.absolute.right-0.mt-2');
            
            if (!notificationButton && !notificationPanel) {
                @this.set('showNotifications', false);
            }
        });
    </script>
</div>