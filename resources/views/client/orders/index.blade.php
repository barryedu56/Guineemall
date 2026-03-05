{{-- resources/views/client/orders/index.blade.php --}}
@extends('client.layout')

@section('title', 'Mes Commandes - GuinéeMall')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold mb-8">Mes Commandes</h1>

    @if($orders->isEmpty())
        <div class="bg-white rounded-xl shadow p-12 text-center">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Aucune commande</h3>
            <p class="text-gray-600 mb-6">Vous n'avez pas encore passé de commande</p>
            <a href="{{ route('client.catalog.index') }}" 
               class="inline-block bg-yellow-600 text-white px-8 py-3 rounded-lg hover:bg-yellow-700 transition">
                Découvrir les produits
            </a>
        </div>
    @else
        <div class="space-y-6">
            @foreach($orders as $order)
                @php
                    $statusKey = $order->public_status_key;
                @endphp
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Commande n°</p>
                            <p class="font-bold text-lg">#{{ $order->id }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                            @livewire('order-status-badge', ['orderId' => $order->id], key('order-status-'.$order->id))
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Montant total</p>
                                <p class="text-2xl font-bold text-yellow-600">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Nombre de vendeurs</p>
                                <p class="text-lg font-semibold">{{ $order->vendorOrders->count() }} vendeur(s)</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Livraison</p>
                                <p class="text-sm">{{ $order->user->delivery_address ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <a href="{{ route('client.orders.show', $order) }}" 
                               class="flex-1 text-center bg-yellow-600 text-white px-6 py-3 rounded-lg hover:bg-yellow-700 transition font-semibold">
                                Voir les détails
                            </a>
                            @if($statusKey === 'pending')
                                <form method="POST" action="{{ route('client.orders.cancel', $order) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="px-6 py-3 border-2 border-red-500 text-red-500 rounded-lg hover:bg-red-50 transition font-semibold"
                                            onclick="return confirm('Annuler cette commande ?')">
                                        Annuler
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
