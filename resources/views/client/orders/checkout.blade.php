{{-- resources/views/client/orders/checkout.blade.php --}}
@extends('client.layout')

@section('title', 'Confirmer la commande - GuinéeMall')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold mb-8 text-center">Confirmer Votre Commande</h1>

    <form action="{{ route('client.orders.store') }}" method="POST" class="bg-white rounded-xl shadow-lg p-8">
        @csrf

        {{-- Order Summary --}}
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Récapitulatif de votre commande</h2>
            
            <div class="space-y-4">
                @foreach($cartItems as $item)
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                        <img src="{{ $item->product->image_url }}" 
                             alt="{{ $item->product->name }}" 
                             class="w-16 h-16 object-cover rounded">
                        <div class="flex-1">
                            <h3 class="font-semibold">{{ $item->product->name }}</h3>
                            <p class="text-sm text-gray-600">
                                {{ number_format($item->price, 0, ',', ' ') }} GNF × {{ $item->quantity }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-yellow-600">
                                {{ number_format($item->subtotal, 0, ',', ' ') }} GNF
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 pt-6 border-t">
                <div class="flex items-center justify-between text-xl font-bold">
                    <span>Total de le commande</span>
                    <span class="text-yellow-600 text-3xl">{{ number_format($total, 0, ',', ' ') }} GNF</span>
                </div>
            </div>
        </div>

        {{-- Delivery Address --}}
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Adresse de Livraison</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Adresse complète *</label>
                    <textarea name="delivery_address" 
                              rows="3" 
                              required
                              class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500"
                              placeholder="Ex: Mme. Fatou Diallo, Marché Central, Conakry">{{ old('delivery_address', auth()->user()->delivery_address) }}</textarea>
                    @error('delivery_address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Ville</label>
                    <input type="text" 
                           name="city" 
                           value="{{ old('city', auth()->user()->city ?? 'Conakry') }}"
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500"
                           placeholder="Conakry">
                </div>
            </div>
        </div>

        {{-- Payment Method --}}
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                Mode de Paiement
                <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            </h2>

            <div class="space-y-3">
                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-yellow-500 transition">
                    <input type="radio" 
                           name="payment_method" 
                           value="cash_on_delivery" 
                           checked
                           class="text-yellow-600 focus:ring-yellow-500">
                    <div class="ml-4 flex items-center gap-3">
                        <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                        </svg>
                        <div>
                            <p class="font-semibold">Paiement à la livraison</p>
                            <p class="text-sm text-gray-600">Payez en espèces à la réception</p>
                        </div>
                    </div>
                </label>

                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-yellow-500 transition opacity-50">
                    <input type="radio" 
                           name="payment_method" 
                           value="mobile_money" 
                           disabled
                           class="text-yellow-600 focus:ring-yellow-500">
                    <div class="ml-4 flex items-center gap-3">
                        <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/>
                        </svg>
                        <div>
                            <p class="font-semibold">Mobile Money</p>
                            <p class="text-sm text-gray-600">Bientôt disponible</p>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Submit Button --}}
        <button type="submit" 
                class="w-full bg-yellow-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-yellow-700 transition">
            Confirmer la commande
        </button>

        <p class="text-center text-sm text-gray-600 mt-4">
            En confirmant, vous acceptez nos conditions générales de vente
        </p>
    </form>
</div>
@endsection