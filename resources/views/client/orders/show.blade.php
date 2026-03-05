@extends('client.layout')

@section('title', 'Détails de la commande #' . $order->id)

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Informations de la commande -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    @php
                        $statusKey = $order->public_status_key;
                    @endphp
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Informations de la commande</h2>
                        @livewire('order-status-badge', ['orderId' => $order->id], key('order-status-show-'.$order->id))
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Numéro de commande</h3>
                            <p class="text-lg font-semibold text-gray-800">#{{ $order->id }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Date de commande</h3>
                            <p class="text-lg text-gray-800">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Montant total</h3>
                            <p class="text-lg font-semibold text-blue-600">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Mode de paiement</h3>
                            <p class="text-lg text-gray-800">{{ $order->payment_method_label }}</p>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $order->payment_status_badge_class }}">
                                {{ $order->payment_status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reçu de paiement (simulé) -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">Reçu de paiement</h2>
                    <button type="button"
                            onclick="window.print()"
                            class="text-sm font-medium text-blue-600 hover:text-blue-800">
                        Imprimer
                    </button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Référence</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $order->payment_reference ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Statut</p>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $order->payment_status_badge_class }}">
                                {{ $order->payment_status_label }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Méthode</p>
                            <p class="text-base text-gray-800">{{ $order->payment_method_label }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Montant</p>
                            <p class="text-base font-semibold text-gray-900">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Date</p>
                            <p class="text-base text-gray-800">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Client</p>
                            <p class="text-base text-gray-800">{{ auth()->user()->name }}</p>
                        </div>
                    </div>
                    <div class="mt-6 border-t pt-4 text-sm text-gray-500">
                        Reçu généré automatiquement par GuinéeMall. Ce document confirme l'enregistrement du paiement.
                    </div>
                </div>
            </div>

            <!-- Articles commandés -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Articles commandés</h2>
                </div>
                
                <div class="divide-y divide-gray-200">
                    @foreach($order->orderItems as $item)
                        <div class="p-6">
                            <div class="flex items-center space-x-4">
                                <!-- Image produit -->
                                <div class="flex-shrink-0">
                                    @if(optional($item->product)->image)
                                        <img src="{{ $item->product->image_url }}" 
                                             alt="{{ $item->product->name ?? 'Produit' }}" 
                                             class="w-16 h-16 object-cover rounded-lg"
                                             loading="lazy">
                                    @else
                                        <div class="w-16 h-16 bg-gray-300 rounded-lg flex items-center justify-center">
                                            <span class="text-gray-500 text-xs">Pas d'image</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Détails -->
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        {{ $item->product->name ?? 'Produit supprimé' }}
                                    </h3>
                                    
                                    <p class="text-sm text-gray-600 mb-2">
                                        Vendu par: {{ optional(optional($item->product)->vendor)->shop_name ?? 'Vendeur inconnu' }}
                                    </p>
                                    
                                    <p class="text-sm text-gray-500">
                                        {{ optional(optional($item->product)->category)->name ?? 'Non catégorisé' }}
                                    </p>
                                </div>

                                <!-- Quantité et prix -->
                                <div class="text-right">
                                    <p class="text-gray-600 mb-1">
                                        Quantité: {{ $item->quantity }}
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ number_format($item->price, 0, ',', ' ') }} GNF / unité
                                    </p>
                                    <p class="font-semibold text-gray-800">
                                        {{ number_format($item->quantity * $item->price, 0, ',', ' ') }} GNF
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Total -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-800">Total de la commande:</span>
                        <span class="text-xl font-bold text-blue-600">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</span>
                    </div>
                </div>
            </div>

            <!-- Informations de livraison -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Informations de livraison</h2>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Nom du client</h3>
                            <p class="text-lg text-gray-800">{{ auth()->user()->name }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Téléphone</h3>
                            <p class="text-lg text-gray-800">{{ auth()->user()->phone }}</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Adresse de livraison</h3>
                            <p class="text-lg text-gray-800">{{ auth()->user()->delivery_address }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <a href="{{ route('client.orders.index') }}" 
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    ← Retour à mes commandes
                </a>
                
                @if(in_array($statusKey, ['pending', 'processing']))
                    <form method="POST" 
                          action="{{ route('client.orders.cancel', $order) }}" 
                          class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande? Cette action est irréversible.')"
                                class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium">
                            Annuler la commande
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
