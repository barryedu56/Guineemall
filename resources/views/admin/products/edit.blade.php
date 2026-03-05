@extends('admin.layouts.app')

@section('title', 'Modifier un produit')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-3xl">

    <h2 class="text-xl font-bold mb-6">Modifier le produit</h2>

    <form method="POST"
          action="{{ route('admin.products.update', $product) }}">
        @csrf
        @method('PUT')

        <!-- Nom -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Nom du produit</label>
            <input type="text"
                   name="name"
                   value="{{ $product->name }}"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Description</label>
            <textarea name="description"
                      class="w-full border rounded px-3 py-2"
                      rows="4">{{ $product->description }}</textarea>
        </div>

        <!-- Prix -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Prix (GNF)</label>
            <input type="number"
                   name="price"
                   value="{{ $product->price }}"
                   class="w-full border rounded px-3 py-2"
                   required>
        </div>

        <!-- Catégorie -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Catégorie</label>
            <select name="category_id"
                    class="w-full border rounded px-3 py-2">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}"
                        @selected($product->category_id === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Vendeur -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Vendeur</label>
            <select name="vendor_id"
                    class="w-full border rounded px-3 py-2">
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}"
                        @selected($product->vendor_id === $vendor->id)>
                        {{ $vendor->shop_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Statut -->
        <div class="mb-6">
            <label class="block mb-1 font-medium">Statut</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="1" @selected($product->status)>Actif</option>
                <option value="0" @selected(!$product->status)>Inactif</option>
            </select>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button class="bg-blue-600 text-white px-5 py-2 rounded">
                Mettre à jour
            </button>

            <a href="{{ route('admin.products.index') }}"
               class="bg-gray-300 px-5 py-2 rounded">
                Annuler
            </a>
        </div>

    </form>
</div>
@endsection
