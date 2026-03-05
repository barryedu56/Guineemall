@extends('admin.layouts.app')

@section('title', 'Modifier la catégorie')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-xl">

    <h2 class="text-xl font-bold mb-4">Modifier catégorie</h2>

    <form method="POST"
          action="{{ route('admin.categories.update', $category) }}">
        @csrf
        @method('PUT')

        @php
            $presetCategories = [
                'Électronique',
                'Téléphones & Accessoires',
                'Informatique',
                'Mode & Vêtements',
                'Chaussures',
                'Beauté & Santé',
                'Maison & Cuisine',
                'Bébé & Enfants',
                'Sports & Loisirs',
                'Alimentation',
                'Supermarché',
                'Auto & Moto',
                'Accessoires',
            ];
        @endphp

        <!-- Nom (liste + saisie) -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Nom de la catégorie</label>
            <select name="preset_name" class="w-full border rounded px-3 py-2 mb-2">
                <option value="">Sélectionnez dans la liste (optionnel)</option>
                @foreach($presetCategories as $preset)
                    <option value="{{ $preset }}" @selected(old('preset_name') === $preset)>{{ $preset }}</option>
                @endforeach
            </select>
            <input type="text"
                   name="custom_name"
                   value="{{ old('custom_name', $category->name) }}"
                   class="w-full border rounded px-3 py-2"
                   placeholder="Ou saisissez un nom personnalisé">
        </div>

        <!-- Statut -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Statut</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="active" @selected($category->status === 'active')>Active</option>
                <option value="inactive" @selected($category->status === 'inactive')>Inactive</option>
            </select>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button class="bg-blue-600 text-white px-4 py-2 rounded">
                Mettre à jour
            </button>

            <a href="{{ route('admin.categories.index') }}"
               class="bg-gray-300 px-4 py-2 rounded">
                Annuler
            </a>
        </div>

    </form>
</div>
@endsection
