{{-- resources/views/vendeur/orders/report.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Rapport de ventes')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Rapport de ventes</h1>
            <p class="text-gray-600 text-sm sm:text-base">Analyse des ventes sur la periode selectionnee</p>
        </div>
        <a href="{{ route('vendeur.orders.index') }}"
           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition flex items-center space-x-2">
            <i class="fas fa-arrow-left"></i>
            <span>Retour aux commandes</span>
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow mb-6">
        <form method="GET" action="{{ route('vendeur.orders.sales-report') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date de debut</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition">
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    {{-- Resume --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <p class="text-sm text-gray-600">Total ventes</p>
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($report['total_revenue'] ?? 0, 0, ',', ' ') }} GNF</p>
        </div>
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <p class="text-sm text-gray-600">Commandes livrées</p>
            <p class="text-2xl font-bold text-gray-900">{{ $report['total_orders'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <p class="text-sm text-gray-600">Produits vendus</p>
            <p class="text-2xl font-bold text-gray-900">{{ $report['products_sold'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 sm:p-6 rounded-lg shadow">
            <p class="text-sm text-gray-600">Moyenne par commande</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($report['average_order_value'] ?? 0, 0, ',', ' ') }} GNF</p>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-full">
                <thead class="bg-gray-100 text-left">
                    <tr>
                        <th class="p-3">Produit</th>
                        <th class="p-3">Quantité</th>
                        <th class="p-3">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($report['top_products'] ?? []) as $row)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3">{{ $row['name'] ?? '—' }}</td>
                            <td class="p-3">{{ $row['quantity'] ?? 0 }}</td>
                            <td class="p-3 font-semibold text-yellow-600">{{ number_format($row['revenue'] ?? 0, 0, ',', ' ') }} GNF</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-6 text-center text-gray-500">Aucune donnee disponible.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
