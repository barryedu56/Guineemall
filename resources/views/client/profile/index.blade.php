{{-- resources/views/client/profile/index.blade.php --}}
@extends('client.layout')

@section('title', 'Mon Profil - GuinéeMall')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold mb-8">Mon Profil</h1>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        {{-- Stats Cards --}}
        <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold">{{ $ordersCount }}</p>
            <p class="text-yellow-100">Commandes</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold">{{ number_format($totalSpent, 0, ',', ' ') }}</p>
            <p class="text-blue-100">Total dépensé (GNF)</p>
        </div>

        <div class="bg-gradient-to-br from-yellow-400 to-orange-500 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold">{{ $loyaltyPoints }}</p>
            <p class="text-yellow-100">Points fidélité</p>
        </div>
    </div>

    {{-- Profile Info --}}
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-xl font-semibold">Informations Personnelles</h2>
            <a href="{{ route('client.profile.edit') }}" 
               class="text-yellow-600 hover:text-yellow-700 font-semibold flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Modifier
            </a>
        </div>

        <div class="p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Nom complet</label>
                    <p class="font-semibold text-lg">{{ $user->name }}</p>
                </div>

                <div>
                    <label class="text-sm text-gray-600 block mb-1">Email</label>
                    <p class="font-semibold text-lg">{{ $user->email }}</p>
                </div>

                <div>
                    <label class="text-sm text-gray-600 block mb-1">Téléphone</label>
                    <p class="font-semibold text-lg">{{ $user->phone ?? 'Non renseigné' }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm text-gray-600 block mb-1">Adresse de livraison</label>
                    <p class="font-semibold text-lg">{{ $user->delivery_address ?? 'Non renseignée' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white rounded-xl shadow mt-6">
        <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-xl font-semibold">Commandes Récentes</h2>
            <a href="{{ route('client.orders.index') }}" class="text-yellow-600 hover:text-yellow-700 font-semibold">
                Voir tout
            </a>
        </div>

        <div class="p-6">
            @if($recentOrders->isNotEmpty())
                <div class="space-y-4">
                    @foreach($recentOrders as $order)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div>
                            <p class="font-semibold">#{{ $order->id }}</p>
                                <p class="text-sm text-gray-600">{{ $order->created_at->format('d/m/Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-yellow-600">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</p>
                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $order->status === 'delivered' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-center text-gray-600 py-8">Aucune commande récente</p>
            @endif
        </div>
    </div>
</div>
@endsection