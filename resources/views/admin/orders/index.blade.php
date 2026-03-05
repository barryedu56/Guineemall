@extends('admin.layouts.app')

@section('title', 'Commandes')

@section('content')

<h2 class="text-xl font-semibold mb-4">
    Liste des commandes
</h2>

@if($orders->isEmpty())
    <div class="bg-yellow-50 text-yellow-700 p-4 rounded">
        Aucune commande enregistrée.
    </div>
@else
    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="w-full min-w-full">
        <thead class="bg-gray-100 text-left">
            <tr>
                <th class="p-3">ID</th>
                <th class="p-3">Client</th>
                <th class="p-3">Vendeur</th>
                <th class="p-3">Total</th>
                <th class="p-3">Statut</th>
                <th class="p-3">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($orders as $order)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3 font-medium">
                        #{{ $order->id }}
                    </td>

                    <td class="p-3">
                        {{ $order->user->name ?? 'Client supprimé' }}
                    </td>

                    <td class="p-3">
                        @php
                            $vendors = $order->vendorOrders->pluck('vendor.shop_name')->filter()->unique();
                        @endphp
                        {{ $vendors->isEmpty() ? '—' : $vendors->join(', ') }}
                    </td>

                    <td class="p-3">
                        {{ number_format($order->total_amount, 0, ',', ' ') }} GNF
                    </td>

                    <td class="p-3">
                        <x-order-status-badge :order="$order" />
                    </td>

                    <td class="p-3">
                        <a href="{{ route('admin.orders.show', $order) }}"
                           class="text-yellow-700 font-medium hover:underline">
                            Voir
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
        </table>
    </div>
@endif

@endsection
