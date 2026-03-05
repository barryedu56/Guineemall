@extends('admin.layouts.app')

@section('title', 'Détail du produit')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-4xl">
    <h2 class="text-xl font-bold mb-6">Détails du produit</h2>

    <div class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <p class="font-medium">Nom</p>
            <p class="text-gray-700">{{ $product->name }}</p>
        </div>
        <div>
            <p class="font-medium">Catégorie</p>
            <p class="text-gray-700">{{ $product->category->name ?? '—' }}</p>
        </div>
        <div>
            <p class="font-medium">Vendeur</p>
            <p class="text-gray-700">{{ $product->vendor->shop_name ?? '—' }}</p>
        </div>
        <div>
            <p class="font-medium">Statut</p>
            <p class="text-gray-700">{{ $product->status === 'active' ? 'Actif' : 'Inactif' }}</p>
        </div>
        <div>
            <p class="font-medium">Prix</p>
            <p class="text-gray-700">{{ number_format($product->price, 0, ',', ' ') }} GNF</p>
        </div>
        <div>
            <p class="font-medium">Stock</p>
            <p class="text-gray-700">{{ $product->stock }}</p>
        </div>
    </div>

    <div class="mb-6">
        <p class="font-medium">Description</p>
        <p class="text-gray-700">{{ $product->description }}</p>
    </div>

    <div class="flex gap-3">
        @if($product->status === 'active')
            <form method="POST" action="{{ route('admin.products.deactivate', $product) }}">
                @csrf
                @method('PATCH')
                <button class="bg-red-600 text-white px-5 py-2 rounded">Désactiver</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.products.activate', $product) }}">
                @csrf
                @method('PATCH')
                <button class="bg-yellow-600 text-white px-5 py-2 rounded">Activer</button>
            </form>
        @endif
        <a href="{{ route('admin.products.index') }}" class="bg-gray-300 px-5 py-2 rounded">Retour</a>
    </div>
</div>
@endsection
