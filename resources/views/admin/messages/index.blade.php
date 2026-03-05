@extends('admin.layouts.app')

@section('title', 'Messages')
@section('page-title', 'Messages')

@section('content')
<div class="slide-in h-[calc(100vh-8rem)]">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Messages</h2>
                <p class="text-sm text-gray-600 mt-1">Communications avec les utilisateurs</p>
            </div>
            <div class="flex items-center space-x-3">
                @if(isset($unreadCount) && $unreadCount > 0)
                    <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">
                        {{ $unreadCount }} non lu{{ $unreadCount > 1 ? 's' : '' }}
                    </span>
                @endif
                <a href="{{ route('admin.dashboard') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour
                </a>
            </div>
        </div>
    </div>

    <!-- WhatsApp-like Layout -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 h-[calc(100%-5rem)]">
        <div class="flex h-full">
            <!-- COLONNE GAUCHE (30%) - Liste des conversations -->
            <div class="w-[30%] border-r border-gray-200 flex flex-col">
                <!-- Barre de recherche -->
                <div class="p-4 border-b border-gray-200">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="search-input"
                            placeholder="Rechercher une conversation..." 
                            class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            x-data="{ searchTimeout: null }"
                            x-on:input="
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(() => {
                                    searchConversations($el.value);
                                }, 300);
                            "
                        />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Liste des conversations -->
                <div class="flex-1 overflow-y-auto">
                    @if(isset($conversations) && $conversations->count() > 0)
                        <div class="divide-y divide-gray-100">
                            @foreach($conversations as $conversation)
                                <a href="{{ route('admin.messages.show', $conversation->id) }}" 
                                   class="block hover:bg-gray-50 transition-colors {{ request()->routeIs('admin.messages.show', $conversation->id) ? 'bg-orange-50 border-l-4 border-orange-500' : '' }}">
                                    <div class="p-4 flex items-center space-x-3">
                                        <!-- Avatar -->
                                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 relative">
                                            @if($conversation->avatar)
                                                <img src="{{ $conversation->avatar }}" alt="{{ $conversation->name }}" 
                                                     class="w-full h-full rounded-full object-cover">
                                            @else
                                                <i class="fas fa-user text-orange-600 text-lg"></i>
                                            @endif
                                            <!-- Badge messages non lus -->
                                            @if($conversation->unread_count > 0)
                                                <span class="absolute -top-1 -right-1 bg-orange-500 text-white text-xs rounded-full px-2 py-0.5 min-w-[20px] text-center font-bold animate-pulse">
                                                    {{ $conversation->unread_count }}
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Infos conversation -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $conversation->name }}
                                                </p>
                                                <span class="text-xs text-gray-500">
                                                    {{ $conversation->last_message_time ?? '' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between mt-1">
                                                <p class="text-xs text-gray-500">
                                                    {{ $conversation->role === 'vendeur' ? 'Vendeur' : ($conversation->role === 'client' ? 'Client' : 'Admin') }}
                                                    @if($conversation->shop_name)
                                                        • {{ $conversation->shop_name }}
                                                    @endif
                                                </p>
                                                <!-- Badge non lu séparé -->
                                                @if($conversation->unread_count > 0)
                                                    <span class="bg-orange-100 text-orange-800 text-xs rounded-full px-2 py-0.5 font-medium">
                                                        {{ $conversation->unread_count }} nouveau{{ $conversation->unread_count > 1 ? 'x' : '' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-center p-8">
                            <i class="fas fa-comments text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 font-medium">Aucune conversation trouvée</p>
                            <p class="text-sm text-gray-400 mt-2">Commencez une nouvelle discussion avec un utilisateur</p>
                        </div>
                    @endif
                </div>

                <!-- Pagination -->
                @if(isset($conversations) && $conversations->hasPages())
                    <div class="p-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                {{ $conversations->firstItem() }}-{{ $conversations->lastItem() }} / {{ $conversations->total() }}
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($conversations->currentPage() > 1)
                                    <a href="{{ $conversations->previousPageUrl() }}" 
                                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm transition-colors">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                @endif
                                
                                <span class="text-sm text-gray-600">
                                    {{ $conversations->currentPage() }} / {{ $conversations->lastPage() }}
                                </span>
                                
                                @if($conversations->hasMorePages())
                                    <a href="{{ $conversations->nextPageUrl() }}" 
                                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm transition-colors">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- COLONNE DROITE (70%) - Zone de conversation -->
            <div class="w-[70%] flex flex-col">
                <!-- Message par défaut quand aucune conversation sélectionnée -->
                <div class="flex-1 flex items-center justify-center text-center p-8">
                    <div class="max-w-md">
                        <i class="fas fa-comments text-6xl text-gray-300 mb-6"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">Sélectionnez une conversation</h3>
                        <p class="text-gray-500">Choisissez une conversation dans la liste pour commencer à discuter</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function searchConversations(query) {
    if (query.trim() === '') {
        window.location.href = '{{ route("admin.messages.index") }}';
        return;
    }
    
    window.location.href = '{{ route("admin.messages.index") }}?search=' + encodeURIComponent(query);
}
</script>
@endsection
