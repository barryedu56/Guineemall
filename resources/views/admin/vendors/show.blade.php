@extends('admin.layouts.app')

@section('title', 'Détail du vendeur')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-4xl">

    <h2 class="text-xl font-bold mb-6">Informations du vendeur</h2>

    <div class="grid grid-cols-2 gap-6 mb-6">

        <div>
            <p class="font-medium">Nom de la boutique</p>
            <p class="text-gray-700">{{ $vendor->shop_name }}</p>
        </div>

        <div>
            <p class="font-medium">Statut</p>
            <span class="px-3 py-1 rounded text-white
                {{ $vendor->status === 'approved' ? 'bg-yellow-600' : 'bg-red-600' }}">
                {{ ucfirst($vendor->status) }}
            </span>
        </div>

        <div>
            <p class="font-medium">Email</p>
            <p class="text-gray-700">{{ $vendor->user->email }}</p>
        </div>

        <div>
            <p class="font-medium">Téléphone</p>
            <p class="text-gray-700">{{ $vendor->user->phone ?? '—' }}</p>
        </div>

        <div>
            <p class="font-medium">Adresse</p>
            <p class="text-gray-700">{{ $vendor->user->delivery_address ?? '—' }}</p>
        </div>

        <div>
            <p class="font-medium">Date d’inscription</p>
            <p class="text-gray-700">{{ $vendor->created_at->format('d/m/Y') }}</p>
        </div>

    </div>

    <!-- ACTIONS ADMIN -->
    <div class="flex gap-3">

        @if($vendor->status !== 'approved')
            <form method="POST"
                  action="{{ route('admin.vendors.approve', $vendor) }}">
                @csrf
                @method('PATCH')

                <button class="bg-yellow-600 text-white px-5 py-2 rounded">
                    Approuver
                </button>
            </form>
        @endif

        @if($vendor->status === 'approved')
            <form method="POST"
                  action="{{ route('admin.vendors.suspend', $vendor) }}">
                @csrf
                @method('PATCH')

                <button class="bg-red-600 text-white px-5 py-2 rounded">
                    Suspendre
                </button>
            </form>
        @endif

        <a href="{{ route('admin.vendors.index') }}"
           class="bg-gray-300 px-5 py-2 rounded">
            Retour
        </a>

    </div>

</div>
@endsection
