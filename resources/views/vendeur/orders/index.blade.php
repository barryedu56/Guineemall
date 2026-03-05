{{-- resources/views/vendeur/orders/index.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Mes Commandes - Espace Vendeur')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- En-tête avec actions --}}
    <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Mes Commandes</h1>
            <p class="text-gray-600 mt-1 text-sm sm:text-base">Gérez les commandes de vos clients</p>
        </div>
        
        <div class="mt-2 sm:mt-0 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('vendeur.orders.sales-report') }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fas fa-chart-line"></i>
                <span>Rapport de ventes</span>
            </a>
            <a href="{{ route('vendeur.orders.export') }}?{{ http_build_query(request()->query()) }}" 
               class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition flex items-center space-x-2">
                <i class="fas fa-download"></i>
                <span>Exporter</span>
            </a>
        </div>
    </div>

    {{-- Statistiques rapides --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total commandes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">En attente</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-box text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">En préparation</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $stats['preparing'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full">
                    <i class="fas fa-check-circle text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Livrées</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['delivered'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow mb-6">
        <form id="filter-form" method="GET" action="{{ route('vendeur.orders.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Recherche --}}
                <div class="md:col-span-2 lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="N° commande, nom ou email client..." 
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                {{-- Filtre statut --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        <option value="">Tous les statuts</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                        <option value="preparing" {{ request('status') == 'preparing' ? 'selected' : '' }}>En préparation</option>
                        <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Prête</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Livrée</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                    </select>
                </div>

                {{-- Période --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                    <select name="period" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        <option value="">Toutes les périodes</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                        <option value="week" {{ request('period') == 'week' ? 'selected' : '' }}>Cette semaine</option>
                        <option value="month" {{ request('period') == 'month' ? 'selected' : '' }}>Ce mois</option>
                        <option value="year" {{ request('period') == 'year' ? 'selected' : '' }}>Cette année</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row justify-end gap-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-filter mr-2"></i>Filtrer
                </button>
                <a href="{{ route('vendeur.orders.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-times mr-1"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>

    {{-- Tableau des commandes --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Commande
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Montant
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($orders as $order)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            #{{ $order->order->id }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $order->orderItems->count() }} article(s)
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-400 text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $order->order->user->name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $order->order->user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-yellow-600">
                                        {{ number_format($order->total_amount, 0, ',', ' ') }} GNF
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @livewire('vendor-order-status-badge', ['status' => $order->status, 'vendorOrderId' => $order->id], key('vendor-status-'.$order->id))
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        {{-- Voir --}}
                                        <a href="{{ route('vendeur.orders.show', $order) }}" 
                                           class="text-blue-600 hover:text-blue-900 transition"
                                           title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        {{-- Actions selon statut --}}
                                        @if($order->status === 'pending')
                                            <button onclick="confirmOrder({{ $order->id }})" 
                                                    class="text-yellow-600 hover:text-yellow-900 transition"
                                                    title="Confirmer">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif

                                        @if($order->status === 'confirmed')
                                            <button onclick="preparingOrder({{ $order->id }})" 
                                                    class="text-orange-600 hover:text-orange-900 transition"
                                                    title="En préparation">
                                                <i class="fas fa-box"></i>
                                            </button>
                                        @endif

                                        @if($order->status === 'preparing')
                                            <button onclick="readyOrder({{ $order->id }})" 
                                                    class="text-yellow-600 hover:text-yellow-900 transition"
                                                    title="Prête">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        @endif

                                        @if($order->status === 'ready')
                                            <button onclick="deliverOrder({{ $order->id }})" 
                                                    class="text-yellow-600 hover:text-yellow-900 transition"
                                                    title="Livrée">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        @endif

                                        @if(in_array($order->status, ['pending', 'confirmed']))
                                            <button onclick="cancelOrder({{ $order->id }})" 
                                                    class="text-red-600 hover:text-red-900 transition"
                                                    title="Annuler">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="{{ $orders->previousPageUrl() }}" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Précédent
                    </a>
                    <a href="{{ $orders->nextPageUrl() }}" 
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Suivant
                    </a>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Affichage de 
                            <span class="font-medium">{{ $orders->firstItem() }}</span>
                            à 
                            <span class="font-medium">{{ $orders->lastItem() }}</span>
                            sur 
                            <span class="font-medium">{{ $orders->total() }}</span>
                            résultats
                        </p>
                    </div>
                    <div>
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-shopping-cart text-gray-400 text-5xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune commande trouvée</h3>
                <p class="text-gray-500">Vous n'avez pas encore reçu de commandes</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// Fonctions pour les actions rapides sur les commandes
function confirmOrder(orderId) {
    if (confirm('Confirmer cette commande ?')) {
        updateOrderStatus(orderId, 'confirmed');
    }
}

function preparingOrder(orderId) {
    if (confirm('Marquer cette commande comme en préparation ?')) {
        updateOrderStatus(orderId, 'preparing');
    }
}

function readyOrder(orderId) {
    if (confirm('Marquer cette commande comme prête pour livraison ?')) {
        updateOrderStatus(orderId, 'ready');
    }
}

function deliverOrder(orderId) {
    if (confirm('Marquer cette commande comme livrée ?')) {
        updateOrderStatus(orderId, 'delivered');
    }
}

function cancelOrder(orderId) {
    const reason = prompt('Raison de l\'annulation :');
    if (reason) {
        updateOrderStatus(orderId, 'cancelled', reason);
    }
}

function updateOrderStatus(orderId, status, reason = '') {
    const formData = new FormData();
    formData.append('status', status);
    if (reason) {
        formData.append('cancel_reason', reason);
    }

    fetch(`/vendeur/orders/${orderId}/status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    });
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full ${
        type === 'success' ? 'bg-yellow-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);
    
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>
@endpush
