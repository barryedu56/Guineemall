@extends('admin.layouts.app')

@section('title', 'Détail du client')

@section('content')
<div class="space-y-6">

    <!-- Infos client -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-bold mb-4">Informations du client</h2>

        <p><strong>Nom :</strong> {{ $client->name }}</p>
        <p><strong>Email :</strong> {{ $client->email }}</p>
        <p>
            <strong>Statut :</strong>
            @if($client->is_active)
                <span class="text-yellow-600 font-semibold">Actif</span>
            @else
                <span class="text-red-600 font-semibold">Bloqué</span>
            @endif
        </p>

        <div class="mt-4 flex flex-col sm:flex-row gap-3">
            @if($client->is_active)
                <form method="POST" action="{{ route('admin.clients.block', $client) }}">
                    @csrf
                    @method('PATCH')
                    <button class="bg-red-600 text-white px-4 py-2 rounded">
                        Bloquer
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.clients.activate', $client) }}">
                    @csrf
                    @method('PATCH')
                    <button class="bg-yellow-600 text-white px-4 py-2 rounded">
                        Débloquer
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Commandes -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-bold mb-4">Commandes du client</h2>

        @if($client->orders->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-full border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2">Commande</th>
                        <th class="p-2">Total</th>
                        <th class="p-2">Statut</th>
                        <th class="p-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->orders as $order)
                        <tr class="border-t">
                            <td class="p-2">#{{ $order->id }}</td>
                            <td class="p-2">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</td>
                            <td class="p-2">{{ ucfirst($order->status) }}</td>
                            <td class="p-2">{{ $order->created_at->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                </table>
            </div>
        @else
            <p>Aucune commande.</p>
        @endif
    </div>

</div>
@endsection
