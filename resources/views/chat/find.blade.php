@extends('layouts.app')

@section('title', 'Trouver une conversation - GuinéeMall')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <!-- Header moderne -->
        <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-white p-6">
            <div class="flex items-center space-x-3">
                <a href="{{ route('chat.index') }}" class="text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-lg">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <i class="fas fa-search text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Trouver une conversation</h1>
                    <p class="text-yellow-100">
                        @if(auth()->user()->role === 'client')
                            Recherchez des vendeurs pour discuter de leurs produits
                        @elseif(auth()->user()->role === 'vendeur')
                            Recherchez vos clients pour leur offrir un meilleur service
                        @else
                            Recherchez des utilisateurs pour communiquer
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Messages d'information par rôle -->
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
            <form id="search-form" class="space-y-4">
                @csrf
                <div class="relative">
                    <input 
                        type="text" 
                        name="query"
                        placeholder="Rechercher par nom ou email..." 
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-all"
                        required
                        minlength="2"
                    >
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-search text-xl"></i>
                    </div>
                    <div id="search-loading" class="absolute right-4 top-1/2 -translate-y-1/2 hidden">
                        <i class="fas fa-spinner fa-spin text-yellow-500"></i>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-6 py-3 rounded-xl font-semibold hover:from-yellow-600 hover:to-orange-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-search mr-2"></i>
                    Rechercher
                </button>
            </form>
        </div>

        <!-- Résultats de recherche -->
        <div id="search-results" class="hidden">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Résultats de recherche</h2>
                <div id="results-container" class="space-y-4">
                    <!-- Les résultats seront insérés ici -->
                </div>
            </div>
        </div>

        <!-- Vendeurs populaires -->
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                @if(auth()->user()->role === 'client')
                    Vendeurs populaires
                @else
                    Clients récents
                @endif
            </h2>
            
            @php
                $allUsers = \App\Models\User::where('id', '!=', auth()->id())
                    ->where('is_active', true)
                    ->where(function($query) {
                        if (auth()->user()->role === 'client') {
                            $query->where('role', 'vendeur');
                        } elseif (auth()->user()->role === 'vendeur') {
                            $query->where('role', 'client');
                        }
                    })
                    ->with('vendor')
                    ->orderBy('name')
                    ->limit(20)
                    ->get();
            @endphp

            @if($allUsers->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($allUsers as $user)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all hover:border-yellow-300 hover:shadow-lg">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        {{ strtoupper(mb_substr($user->name, 0, 1, 'UTF-8')) }}
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $user->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                        @if($user->vendor && $user->vendor->shop_name)
                                            <p class="text-xs text-yellow-600">🏪 {{ $user->vendor->shop_name }}</p>
                                        @endif
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    @if($user->role === 'vendeur')
                                        🏪 Vendeur
                                    @elseif($user->role === 'client')
                                        👤 Client
                                    @else
                                        🛡️ Admin
                                    @endif
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    @if($user->phone)
                                        <span class="flex items-center">
                                            <i class="fas fa-phone mr-1"></i>
                                            {{ $user->phone }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Pas de téléphone</span>
                                    @endif
                                </div>
                                <a href="{{ route('chat.show', $user) }}" 
                                   class="bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white px-4 py-2 rounded-lg transition-colors text-sm font-semibold shadow-md hover:shadow-lg transform hover:scale-105">
                                    <i class="fas fa-comment mr-2"></i>
                                    Discuter
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if($allUsers->count() >= 20)
                    <div class="text-center mt-6">
                        <p class="text-sm text-gray-500">
                            Affichage des 20 premiers résultats. Utilisez la recherche pour trouver plus d'utilisateurs.
                        </p>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-600">
                        @if(auth()->user()->role === 'client')
                            Aucun vendeur disponible pour le moment.
                        @else
                            Aucun client disponible pour le moment.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Gestion de la recherche
document.getElementById('search-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const query = formData.get('query');
    const loadingEl = document.getElementById('search-loading');
    const resultsEl = document.getElementById('search-results');
    const containerEl = document.getElementById('results-container');
    
    if (query.length < 2) {
        showNotification('Veuillez entrer au moins 2 caractères', 'warning');
        return;
    }
    
    // Afficher le chargement
    loadingEl.classList.remove('hidden');
    resultsEl.classList.add('hidden');
    
    try {
        const response = await fetch('{{ route('chat.search') }}?query=' + encodeURIComponent(query), {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        // Afficher les résultats
        displaySearchResults(data.conversations);
        
    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
        showNotification('Erreur lors de la recherche', 'error');
    } finally {
        loadingEl.classList.add('hidden');
    }
});

function displaySearchResults(conversations) {
    const resultsEl = document.getElementById('search-results');
    const containerEl = document.getElementById('results-container');
    
    if (conversations.length === 0) {
        containerEl.innerHTML = `
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-2xl text-gray-400"></i>
                </div>
                <p class="text-gray-600">Aucun résultat trouvé pour votre recherche.</p>
            </div>
        `;
    } else {
        containerEl.innerHTML = conversations.map(conv => {
            const otherUser = conv.sender_id === {{ auth()->id() }} ? conv.receiver : conv.sender;
            return `
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all hover:border-yellow-300">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-full flex items-center justify-center text-white font-semibold">
                                ${otherUser.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-900">${otherUser.name}</h3>
                                <p class="text-sm text-gray-500">${otherUser.email}</p>
                                <p class="text-xs text-gray-400">Dernier message: ${new Date(conv.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>
                        <a href="{{ route('chat.show', '') }}/${otherUser.id}" 
                           class="bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white px-4 py-2 rounded-lg transition-colors text-sm font-semibold">
                            <i class="fas fa-comment mr-2"></i>
                            Discuter
                        </a>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    resultsEl.classList.remove('hidden');
}

// Notification simple
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-medium shadow-lg z-50 ${
        type === 'error' ? 'bg-red-500' : 
        type === 'warning' ? 'bg-orange-500' : 
        type === 'success' ? 'bg-green-500' : 
        'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endsection
