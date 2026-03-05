@extends('admin.layouts.app')

@section('title', 'Gestion des catégories')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <p class="text-gray-600">
        Cette section permet d’organiser les catégories du marché.
    </p>
    <a href="{{ route('admin.categories.create') }}"
       class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 text-center">
        Ajouter une catégorie
    </a>
</div>

<div class="bg-white rounded shadow overflow-hidden">
    @if($categories->isEmpty())
        <div class="p-4 text-gray-600">
            Aucune catégorie créée. Les vendeurs ne pourront pas ajouter de produits tant qu’aucune catégorie active n’existe.
        </div>
    @else
        @if($categories->where('status', 'active')->isEmpty())
            <div class="p-4 bg-yellow-50 text-yellow-800 border-b">
                Aucune catégorie active. Activez au moins une catégorie pour permettre aux vendeurs d’ajouter des produits.
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full min-w-full">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="p-3">Nom</th>
                    <th class="p-3">Statut</th>
                    <th class="p-3">Créée le</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 font-medium">{{ $category->name }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-sm
                                {{ $category->status === 'active' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-200 text-gray-700' }}">
                                {{ $category->status === 'active' ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="p-3">{{ $category->created_at->format('d/m/Y') }}</td>
                        <td class="p-3">
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 items-start sm:items-center">
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   class="text-blue-600 hover:underline">Modifier</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                      onsubmit="return confirm('Supprimer cette catégorie ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">Supprimer</button>
                                </form>
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
