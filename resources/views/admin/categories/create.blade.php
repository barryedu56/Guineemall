@extends('admin.layouts.app')

@section('title', 'Ajouter une catégorie')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-xl">

    <h2 class="text-xl font-bold mb-4">Nouvelle catégorie</h2>

    @if($errors->any())
        <div class="mb-4 bg-red-50 text-red-700 p-3 rounded">
            <p class="font-semibold mb-1">Veuillez corriger les erreurs :</p>
            <ul class="list-disc pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf

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
                   value="{{ old('custom_name') }}"
                   class="w-full border rounded px-3 py-2"
                   placeholder="Ou saisissez un nom personnalisé">
        </div>

        <!-- Statut -->
        <div class="mb-4">
            <label class="block mb-1 font-medium">Statut</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <button class="bg-yellow-600 text-white px-4 py-2 rounded">
                Enregistrer
            </button>

            <a href="{{ route('admin.categories.index') }}"
               class="bg-gray-300 px-4 py-2 rounded">
                Annuler
            </a>
        </div>

    </form>
</div>
@endsection
