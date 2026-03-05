@extends('layouts.app')

@section('title', 'Trouver un interlocuteur - GuinéeMall')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-search text-white"></i>
                        </div>
                        Trouver un interlocuteur
                    </h1>
                </div>
                <a href="{{ route('chat.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour aux messages
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(auth()->user()->role === 'client')
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>
                    Clients : Discutez avec les vendeurs
                </h2>
                <p class="text-sm text-blue-700">
                    En tant que client, vous pouvez contacter directement les vendeurs pour poser des questions sur leurs produits, négocier les prix ou obtenir de l'aide.
                </p>
            </div>
        @elseif(auth()->user()->role === 'vendeur')
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-yellow-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>
                    Vendeurs : Discutez avec vos clients
                </h2>
                <p class="text-sm text-yellow-700">
                    En tant que vendeur, vous pouvez communiquer avec vos clients pour répondre à leurs questions, confirmer les commandes et offrir un meilleur service.
                </p>
            </div>
        @endif

        <!-- Formulaire de recherche -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form id="search-form">
                @csrf
                <div class="relative">
                    <input type="text" 
                           id="search-input"
                           name="query" 
                           placeholder="Rechercher un @if(auth()->user()->role === 'client') vendeur @else client @endif..." 
                           class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <i class="fas fa-search absolute left-4 top-4 text-gray-400"></i>
                    <button type="submit" 
                            class="absolute right-2 top-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                        Rechercher
                    </button>
                </div>
            </form>
        </div>

        <!-- Résultats de recherche -->
        <div id="search-results" class="mb-8">
            <!-- Les résultats seront chargés ici via AJAX -->
        </div>

        <!-- Liste de tous les interlocuteurs disponibles -->
        @if(auth()->user()->role === 'vendeur')
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-users mr-2"></i>
                    Tous les clients disponibles
                </h2>
                
                @php
                $allClients = \App\Models\User::where('role', 'client')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->limit(20)
                    ->get();
                @endphp

                @if($allClients->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($allClients as $client)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all hover:border-blue-300">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(mb_substr($client->name, 0, 1, 'UTF-8')) }}
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ $client->name }}</h3>
                                            <p class="text-sm text-gray-500">{{ $client->email }}</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        👤 Client
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-600">
                                        @if($client->phone)
                                            <span class="flex items-center">
                                                <i class="fas fa-phone mr-1"></i>
                                                {{ $client->phone }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">Pas de téléphone</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('chat.show', $client) }}" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                                        <i class="fas fa-comment mr-2"></i>
                                        Contacter
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($allClients->count() >= 20)
                        <div class="text-center mt-6">
                            <p class="text-sm text-gray-500">
                                Affichage des 20 premiers clients. Utilisez la recherche pour trouver des clients spécifiques.
                            </p>
                        </div>
                    @endif
                @else
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-users text-4xl mb-2"></i>
                        <p>Aucun client disponible pour le moment</p>
                    </div>
                @endif
            </div>
        @else
            <!-- Pour les clients, afficher les vendeurs -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-store mr-2"></i>
                    Tous les vendeurs disponibles
                </h2>
                
                @php
                $allVendors = \App\Models\User::where('role', 'vendeur')
                    ->where('is_active', true)
                    ->with('vendor')
                    ->orderBy('name')
                    ->limit(20)
                    ->get();
                @endphp

                @if($allVendors->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($allVendors as $vendor)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all hover:border-yellow-300">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-full flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(mb_substr($vendor->name, 0, 1, 'UTF-8')) }}
                                        </div>
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ $vendor->name }}</h3>
                                            <p class="text-sm text-gray-500">{{ $vendor->email }}</p>
                                            @if($vendor->vendor && $vendor->vendor->shop_name)
                                                <p class="text-xs text-yellow-600">🏪 {{ $vendor->vendor->shop_name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        🏪 Vendeur
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-600">
                                        @if($vendor->phone)
                                            <span class="flex items-center">
                                                <i class="fas fa-phone mr-1"></i>
                                                {{ $vendor->phone }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">Pas de téléphone</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('chat.show', $vendor) }}" 
                                       class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition-colors text-sm">
                                        <i class="fas fa-comment mr-2"></i>
                                        Contacter
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($allVendors->count() >= 20)
                        <div class="text-center mt-6">
                            <p class="text-sm text-gray-500">
                                Affichage des 20 premiers vendeurs. Utilisez la recherche pour trouver des vendeurs spécifiques.
                            </p>
                        </div>
                    @endif
                @else
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-store text-4xl mb-2"></i>
                        <p>Aucun vendeur disponible pour le moment</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Interlocuteurs récents -->
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-history mr-2"></i>
                Interlocuteurs récents
            </h2>
            <div id="recent-conversations" class="space-y-3">
                @php
                $recentConversations = App\Models\Message::where('sender_id', auth()->id())
                    ->orWhere('receiver_id', auth()->id())
                    ->with(['sender', 'receiver'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->groupBy(function ($message) {
                        return $message->sender_id === auth()->id() ? $message->receiver_id : $message->sender_id;
                    })
                    ->map(function ($messages) {
                        return $messages->first();
                    })
                    ->take(5);
                @endphp

                @if($recentConversations->count() > 0)
                    @foreach($recentConversations as $message)
                        <?php
                        $otherUser = $message->sender_id === auth()->id() ? $message->receiver : $message->sender;
                        
                        // Vérifier si la conversation est autorisée selon le rôle
                        $canChat = false;
                        if (auth()->user()->role === 'client' && $otherUser->role === 'vendeur') {
                            $canChat = true;
                        } elseif (auth()->user()->role === 'vendeur' && $otherUser->role === 'client') {
                            $canChat = true;
                        }
                        ?>
                        
                        @if($canChat)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        {{ strtoupper(mb_substr($otherUser->name, 0, 1, 'UTF-8')) }}
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $otherUser->name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            @if($otherUser->role === 'vendeur')
                                                🏪 Vendeur
                                            @else
                                                👤 Client
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('chat.show', $otherUser) }}" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                    <i class="fas fa-comment mr-2"></i>
                                    Discuter
                                </a>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-comments text-4xl mb-2"></i>
                        <p>Aucune conversation récente</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const query = searchInput.value.trim();
        if (query.length < 2) {
            searchResults.innerHTML = '<div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500 py-4">Veuillez entrer au moins 2 caractères</div>';
            return;
        }

        // Afficher un indicateur de chargement
        searchResults.innerHTML = '<div class="bg-white rounded-lg shadow-md p-6 text-center py-4"><i class="fas fa-spinner fa-spin text-blue-600"></i> Recherche en cours...</div>';

        // Envoyer la requête AJAX
        fetch('/chat/search?query=' + encodeURIComponent(query), {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.conversations && data.conversations.length > 0) {
                let html = '<div class="bg-white rounded-lg shadow-md p-6"><h3 class="text-lg font-semibold text-gray-800 mb-4">Résultats de recherche</h3><div class="space-y-3">';
                data.conversations.forEach(function(conversation) {
                    const otherUser = conversation.sender_id === {{ auth()->id() }} ? conversation.receiver : conversation.sender;
                    const roleText = otherUser.role === 'vendeur' ? 'Vendeur' : 'Client';
                    const roleColor = otherUser.role === 'vendeur' ? 'green' : 'blue';
                    const roleIcon = otherUser.role === 'vendeur' ? 'store' : 'user';
                    
                    html += `
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-${roleColor}-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                    ${otherUser.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">${otherUser.name}</h3>
                                    <p class="text-sm text-gray-500">${roleText}</p>
                                </div>
                            </div>
                            <a href="/chat/${otherUser.id}" 
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                                <i class="fas fa-comment mr-2"></i>
                                Discuter
                            </a>
                        </div>
                    `;
                });
                html += '</div></div>';
                searchResults.innerHTML = html;
            } else {
                searchResults.innerHTML = '<div class="bg-white rounded-lg shadow-md p-6 text-center text-gray-500 py-4">Aucun résultat trouvé</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            searchResults.innerHTML = '<div class="bg-white rounded-lg shadow-md p-6 text-center text-red-500 py-4">Erreur lors de la recherche</div>';
        });
    });
});
</script>
@endsection
