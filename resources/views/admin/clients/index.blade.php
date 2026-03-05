@extends('admin.layouts.app')

@section('title', 'Gestion des clients')

@section('content')
<p class="text-gray-600 mb-4">
    Gestion des comptes clients et de leur activité.
</p>

<div class="bg-white rounded shadow overflow-hidden">
    @if($clients->isEmpty())
        <div class="p-4 text-gray-600">
            Aucun client enregistré.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-full">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="p-3">Nom</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Statut</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clients as $client)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 font-medium">{{ $client->name }}</td>
                        <td class="p-3">{{ $client->email }}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-sm
                                {{ $client->is_active ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700' }}">
                                {{ $client->is_active ? 'Actif' : 'Bloqué' }}
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 items-start sm:items-center">
                                <a href="{{ route('admin.clients.show', $client) }}"
                                   class="text-blue-600 hover:underline">Voir</a>
                                @if($client->is_active)
                                    <form method="POST" action="{{ route('admin.clients.block', $client) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-red-600 hover:underline">Bloquer</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.clients.activate', $client) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-yellow-600 hover:underline">Activer</button>
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
