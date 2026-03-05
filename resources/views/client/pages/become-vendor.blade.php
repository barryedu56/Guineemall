@extends('client.layout')

@section('title', 'Devenir Vendeur - GuinéeMall')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-bold mb-4">Devenir Vendeur</h1>
    <p class="text-gray-700 mb-4">
        Vous souhaitez vendre sur GuinéeMall ? Créez un compte vendeur puis soumettez votre boutique à validation.
    </p>

    @auth
        @if(auth()->user()->role === 'vendeur')
            <a href="{{ route('vendeur.profile.create') }}" class="inline-block bg-yellow-600 text-white px-6 py-3 rounded hover:bg-yellow-700">
                Créer ma boutique
            </a>
        @else
            <div class="bg-yellow-50 text-yellow-800 p-4 rounded mb-4">
                Vous êtes connecté en tant que client. Pour créer une boutique, créez un compte vendeur.
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="bg-gray-800 text-white px-6 py-3 rounded hover:bg-gray-900">
                    Se déconnecter pour créer un compte vendeur
                </button>
            </form>
        @endif
    @else
        <a href="{{ route('register') }}" class="inline-block bg-yellow-600 text-white px-6 py-3 rounded hover:bg-yellow-700">
            Créer un compte vendeur
        </a>
    @endauth
</div>
@endsection
