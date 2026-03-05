@extends('admin.layouts.app')

@section('title', 'Gestion des produits')

@section('content')
<div class="admin-card p-6 mb-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Produits</h2>
    <p class="text-gray-600">
        Liste globale de tous les produits publiés par les vendeurs.
    </p>
</div>

<div class="admin-table">
    @if($products->isEmpty())
        <div class="p-8 text-center text-gray-600">
            <i class="fas fa-box text-4xl mb-4 text-gray-400"></i>
            <p>Aucun produit disponible.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
            <thead class="admin-table-thead">
                <tr>
                    <th class="p-4 text-left font-semibold text-gray-700">Produit</th>
                    <th class="p-4 text-left font-semibold text-gray-700">Catégorie</th>
                    <th class="p-4 text-left font-semibold text-gray-700">Vendeur</th>
                    <th class="p-4 text-left font-semibold text-gray-700">Statut</th>
                    <th class="p-4 text-left font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="p-4">
                            <div class="flex items-center space-x-3">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                                @else
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-box text-gray-400"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900">{{ $product->name }}</p>
                                    <p class="text-sm text-gray-500">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 text-gray-700">{{ $product->category->name ?? '—' }}</td>
                        <td class="p-4 text-gray-700">{{ $product->vendor->shop_name ?? '—' }}</td>
                        <td class="p-4">
                            <span class="admin-badge {{ $product->status === 'active' ? 'admin-badge-success' : 'admin-badge-warning' }}">
                                {{ $product->status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="{{ route('admin.products.show', $product) }}"
                                   class="admin-link">
                                    <i class="fas fa-eye mr-1"></i>Voir
                                </a>
                                @if($product->status === 'active')
                                    <form method="POST" action="{{ route('admin.products.deactivate', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="admin-link text-red-600 hover:text-red-700">
                                            <i class="fas fa-ban mr-1"></i>Désactiver
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.products.activate', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="admin-link text-green-600 hover:text-green-700">
                                            <i class="fas fa-check mr-1"></i>Activer
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
</div>
@endsection
