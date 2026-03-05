<div wire:poll.10s>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <h3 class="text-lg font-medium text-gray-900">Statut de la commande</h3>
        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
            @if($status === 'pending') bg-yellow-100 text-yellow-800
            @elseif($status === 'confirmed') bg-blue-100 text-blue-800
            @elseif($status === 'preparing') bg-orange-100 text-orange-800
            @elseif($status === 'ready') bg-indigo-100 text-indigo-800
            @elseif($status === 'delivered') bg-yellow-100 text-yellow-800
            @else bg-red-100 text-red-800
            @endif">
            {{ $statusLabel }}
        </span>
    </div>

    {{-- Timeline du workflow --}}
    <div class="space-y-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-10 h-10 rounded-full 
                    @if($status === 'pending') bg-yellow-500 text-white
                    @elseif(in_array($status, ['confirmed', 'preparing', 'ready', 'delivered'])) bg-yellow-500 text-white
                    @else bg-gray-300 text-white
                    @endif">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">En attente</h4>
                <p class="text-sm text-gray-500">Commande reçue</p>
            </div>
        </div>

        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-10 h-10 rounded-full 
                    @if(in_array($status, ['confirmed', 'preparing', 'ready', 'delivered'])) bg-yellow-500 text-white
                    @else bg-gray-300 text-white
                    @endif">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">Confirmée</h4>
                <p class="text-sm text-gray-500">Commande validée</p>
            </div>
        </div>

        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-10 h-10 rounded-full 
                    @if(in_array($status, ['preparing', 'ready', 'delivered'])) bg-yellow-500 text-white
                    @else bg-gray-300 text-white
                    @endif">
                    <i class="fas fa-box"></i>
                </div>
            </div>
            <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">En préparation</h4>
                <p class="text-sm text-gray-500">Articles en cours de préparation</p>
            </div>
        </div>

        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-10 h-10 rounded-full 
                    @if(in_array($status, ['ready', 'delivered'])) bg-yellow-500 text-white
                    @else bg-gray-300 text-white
                    @endif">
                    <i class="fas fa-truck"></i>
                </div>
            </div>
            <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">Prête</h4>
                <p class="text-sm text-gray-500">Commande prête pour livraison</p>
            </div>
        </div>

        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-10 h-10 rounded-full 
                    @if($status === 'delivered') bg-yellow-500 text-white
                    @else bg-gray-300 text-white
                    @endif">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="ml-4">
                <h4 class="text-sm font-medium text-gray-900">Livrée</h4>
                <p class="text-sm text-gray-500">Commande livrée au client</p>
            </div>
        </div>
    </div>

    {{-- Actions rapides --}}
    <div class="mt-6 flex flex-col sm:flex-row flex-wrap gap-3">
        @if($status === 'pending')
            <form action="{{ route('vendeur.orders.confirm', $order) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition">
                    <i class="fas fa-check mr-2"></i>Confirmer
                </button>
            </form>
        @endif

        @if($status === 'confirmed')
            <form action="{{ route('vendeur.orders.preparing', $order) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition">
                    <i class="fas fa-box mr-2"></i>En préparation
                </button>
            </form>
        @endif

        @if($status === 'preparing')
            <form action="{{ route('vendeur.orders.ready', $order) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-truck mr-2"></i>Prête pour livraison
                </button>
            </form>
        @endif

        @if($status === 'ready')
            <form action="{{ route('vendeur.orders.delivered', $order) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition">
                    <i class="fas fa-check-circle mr-2"></i>Marquer comme livrée
                </button>
            </form>
        @endif

        @if(in_array($status, ['pending', 'confirmed']))
            <button onclick="showCancelModal()" 
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-times mr-2"></i>Annuler
            </button>
        @endif
    </div>
</div>
