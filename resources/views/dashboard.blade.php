    <tool_call>write_to_file <arg_key>TargetFile</arg_key> <arg_value>c:/Users/Amadou/guineemall/guineemall/resources/views/vendeur/dashboard.blade.php</arg_value> <arg_key>CodeContent</arg_key> <arg_value>{{-- resources/views/vendeur/dashboard.blade.php --}} @extends('vendeur.layouts.app')

@section('title', 'Tableau de bord - Espace Vendeur')

@section('content')
    <div class="flex space-x-3">
        <a href="{{ route('vendeur.products.create') }}" 
           class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Ajouter un produit</span>
        </a>
        <a href="{{ route('vendeur.orders.sales-report') }}" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
            <i class="fas fa-chart-line"></i>
            <span>Rapport de ventes</span>
        </a>
    </div>
</div>

{{-- Statistiques principales --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-box text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total produits</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalProducts }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
            <div class="p-3 bg-red-100 rounded-full">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Rupture de stock</p>
                <p class="text-2xl font-bold text-red-600">{{ $outOfStockProducts }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full">
                <i class="fas fa-shopping-cart text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Commandes reçues</p>
                <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full">
                <i class="fas fa-money-bill-wave text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Chiffre d'affaires</p>
                <p class="text-2xl font-bold text-yellow-600">{{ number_format($totalRevenue, 0, ',', ' ') }} GNF</p>
            </div>
        </div>
    </div>
</div>

{{-- Graphique et commandes récentes --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    {{-- Graphique des ventes --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4">Évolution des ventes</h2>
        <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
            <div class="text-center">
                <i class="fas fa-chart-line text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-600">Graphique des ventes</p>
                <p class="text-sm text-gray-500 mt-2">Intégration Chart.js prévue</p>
            </div>
        </div>
    </div>

    {{-- Dernières commandes --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Dernières commandes</h2>
            <a href="{{ route('vendeur.orders.index') }}" class="text-yellow-600 hover:text-yellow-700 text-sm">
                Voir tout →
            </a>
        </div>
        
        @if($recentOrders->count() > 0)
            <div class="space-y-3">
                @foreach($recentOrders as $order)
                    <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium">Commande #{{ $order->order->id }}</p>
                                <p class="text-sm text-gray-600">{{ $order->order->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-yellow-600">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</p>
                                <span class="inline-block px-2 py-1 text-xs rounded-full
                                    @if($order->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($order->status === 'confirmed') bg-blue-100 text-blue-800
                                    @elseif($order->status === 'preparing') bg-orange-100 text-orange-800
                                    @elseif($order->status === 'ready') bg-indigo-100 text-indigo-800
                                    @elseif($order->status === 'delivered') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                <p>Aucune commande pour le moment</p>
            </div>
        @endif
    </div>
</div>

{{-- Produits à réapprovisionner --}}
@if($lowStockProducts->count() > 0)
    <div class="mt-8 bg-white p-6 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-4 text-orange-600">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Produits à réapprovisionner
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($lowStockProducts as $product)
                <div class="border rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        @if($product->image)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-12 h-12 object-cover rounded">
                        @else
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                <i class="fas fa-box text-gray-400"></i>
                            </div>
                        @endif
                        <div class="flex-1">
                            <p class="font-medium">{{ $product->name }}</p>
                            <p class="text-sm text-orange-600">Stock: {{ $product->stock }}</p>
                        </div>
                        <a href="{{ route('vendeur.products.edit', $product) }}" 
                           class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@push('scripts')

@endpush</arg_value> <arg_key>EmptyFile</arg_key> <arg_value>false</arg_value> </tool_call>