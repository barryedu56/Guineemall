{{-- resources/views/vendeur/orders/show.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Commande #' . $order->order->id . ' - Détails')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- En-tête --}}
    <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Commande #{{ $order->order->id }}</h1>
            <p class="text-gray-600 mt-1 text-sm sm:text-base">Détails complets de la commande</p>
        </div>
        
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('vendeur.orders.index') }}" 
               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span>Retour aux commandes</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
        {{-- Informations principales --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Statut et actions --}}
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                @livewire('vendor-order-status-panel', ['vendorOrderId' => $order->id], key('vendor-panel-'.$order->id))
            </div>

            <!-- Articles commandés -->
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Articles commandés</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Produit
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Prix unitaire
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantité
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($order->orderItems as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if(optional($item->product)->image_url)
                                                <img src="{{ $item->product->image_url }}" 
                                                     alt="{{ $item->product->name ?? 'Produit' }}" 
                                                     class="h-10 w-10 rounded-lg object-cover mr-3">
                                            @else
                                                <div class="h-10 w-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                                    <i class="fas fa-image text-gray-400 text-sm"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $item->product->name ?? 'Produit supprimé' }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ optional(optional($item->product)->category)->name ?? 'Non catégorisé' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ number_format($item->price, 0, ',', ' ') }} GNF
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-yellow-600">
                                            {{ number_format($item->quantity * $item->price, 0, ',', ' ') }} GNF
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Total --}}
                <div class="mt-6 border-t pt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-medium text-gray-900">Total de la commande</span>
                        <span class="text-2xl font-bold text-yellow-600">
                            {{ number_format($order->total_amount, 0, ',', ' ') }} GNF
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Informations latérales --}}
        <div class="space-y-6">
            {{-- Informations client --}}
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations client</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Nom</label>
                        <p class="text-gray-900">{{ $order->order->user->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-gray-900">{{ $order->order->user->email }}</p>
                    </div>
                    @if($order->order->user->phone)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Téléphone</label>
                            <p class="text-gray-900">{{ $order->order->user->phone }}</p>
                        </div>
                    @endif
                    @if($order->order->user->delivery_address)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Adresse de livraison</label>
                            <p class="text-gray-900 text-sm">{{ $order->order->user->delivery_address }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Informations commande --}}
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations commande</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Numéro commande</label>
                        <p class="text-gray-900">#{{ $order->order->id }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Date de commande</label>
                        <p class="text-gray-900">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Dernière mise à jour</label>
                        <p class="text-gray-900">{{ $order->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Vendeur</label>
                        <p class="text-gray-900">{{ auth()->user()->vendor->shop_name }}</p>
                    </div>
                </div>
            </div>

            {{-- Reçu de paiement --}}
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Reçu de paiement</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Statut</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $order->order->payment_status_badge_class }}">
                            {{ $order->order->payment_status_label }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Méthode</span>
                        <span class="text-sm font-medium text-gray-900">{{ $order->order->payment_method_label }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Référence</span>
                        <span class="text-sm font-medium text-gray-900">{{ $order->order->payment_reference ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Montant</span>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ number_format($order->order->total_amount, 0, ',', ' ') }} GNF
                        </span>
                    </div>
                </div>
                <div class="mt-4 border-t pt-3 text-xs text-gray-500">
                    Reçu généré automatiquement pour le vendeur.
                </div>
            </div>

            {{-- Résumé financier --}}
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Résumé financier</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Sous-total</span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ number_format($order->total_amount, 0, ',', ' ') }} GNF
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Frais de livraison</span>
                        <span class="text-sm font-medium text-gray-900">0 GNF</span>
                    </div>
                    <div class="border-t pt-3 flex justify-between">
                        <span class="text-lg font-medium text-gray-900">Total</span>
                        <span class="text-xl font-bold text-yellow-600">
                            {{ number_format($order->total_amount, 0, ',', ' ') }} GNF
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal d'annulation --}}
<div x-data="{ showCancelModal: false }" 
     x-show="showCancelModal" 
     x-cloak
     class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
     style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white">
        <form action="{{ route('vendeur.orders.cancel', $order) }}" method="POST">
            @csrf
            <div class="mt-3">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center">
                    Annuler la commande
                </h3>
                <div class="mt-2 px-7 py-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Raison de l'annulation</label>
                    <textarea name="cancel_reason" 
                              rows="3" 
                              required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              placeholder="Expliquez pourquoi vous annulez cette commande..."></textarea>
                </div>
                <div class="flex justify-center space-x-4 mt-4">
                    <button type="button" 
                            @click="showCancelModal = false" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Confirmer l'annulation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function showCancelModal() {
    document.querySelector('[x-data]').__x.$data.showCancelModal = true;
}
</script>
@endsection
