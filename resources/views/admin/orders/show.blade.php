@extends('admin.layouts.app')

@section('title', 'Détail de la commande')

@section('content')

<h2 class="text-2xl font-semibold mb-6">
    Commande #{{ $order->id }}
</h2>

{{-- Informations générales --}}
<div class="bg-white rounded shadow p-6 mb-6">
    <h3 class="font-semibold text-lg mb-4">Informations générales</h3>

    <p><strong>Client :</strong> {{ $order->user->name ?? 'Client supprimé' }}</p>
    <p><strong>Email :</strong> {{ $order->user->email ?? '—' }}</p>
    <p><strong>Vendeurs :</strong>
        @php
            $vendors = $order->vendorOrders->pluck('vendor.shop_name')->filter()->unique();
        @endphp
        {{ $vendors->isEmpty() ? '—' : $vendors->join(', ') }}
    </p>

    <p class="mt-2">
        <strong>Statut :</strong>
        @livewire('order-status-badge', ['orderId' => $order->id], key('admin-order-status-show-'.$order->id))
    </p>

    <p class="mt-2">
        <strong>Total :</strong>
        {{ number_format($order->total_amount, 0, ',', ' ') }} GNF
    </p>
</div>

{{-- Changement de statut --}}
<div class="bg-white rounded shadow p-6 mb-6">
    <h3 class="font-semibold text-lg mb-4">Changer le statut</h3>

    <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="flex flex-col sm:flex-row gap-3 sm:items-center">
        @csrf
        @method('PATCH')

        <select name="status" class="border rounded p-2 sm:mr-2 w-full sm:w-auto">
            <option value="pending" @selected($order->status === 'pending')>En attente</option>
            <option value="processing" @selected($order->status === 'processing')>En préparation</option>
            <option value="delivered" @selected($order->status === 'delivered')>Livrée</option>
            <option value="cancelled" @selected($order->status === 'cancelled')>Annulée</option>
        </select>

        <button class="bg-yellow-700 text-white px-4 py-2 rounded hover:bg-yellow-800 w-full sm:w-auto">
            Mettre à jour
        </button>
    </form>
</div>

{{-- Produits --}}
<div class="bg-white rounded shadow p-6">
    <h3 class="font-semibold text-lg mb-4">Produits commandés</h3>

    <div class="overflow-x-auto">
        <table class="w-full min-w-full border">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2 text-left">Produit</th>
                <th class="border p-2 text-center">Quantité</th>
                <th class="border p-2 text-right">Prix</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td class="border p-2">
                        {{ optional($item->product)->name ?? 'Produit supprimé' }}
                    </td>
                    <td class="border p-2 text-center">
                        {{ $item->quantity }}
                    </td>
                    <td class="border p-2 text-right">
                        {{ number_format($item->price, 0, ',', ' ') }} GNF
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center p-4 text-gray-500">
                        Aucun produit dans cette commande.
                    </td>
                </tr>
            @endforelse
        </tbody>
        </table>
    </div>
</div>

@endsection
