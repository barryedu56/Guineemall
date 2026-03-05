{{-- resources/views/client/profile/edit.blade.php --}}
@extends('client.layout')

@section('title', 'Modifier mon profil - GuinéeMall')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('client.profile.index') }}" class="text-yellow-600 hover:text-yellow-700 inline-flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour au profil
        </a>
    </div>

    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b">
            <h1 class="text-2xl font-bold">Modifier mon profil</h1>
        </div>

        <form action="{{ route('client.profile.update') }}" method="POST" class="p-6">
            @csrf
            @method('PATCH')

            <div class="space-y-6">
                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Nom complet *</label>
                    <input type="text" 
                           name="name" 
                           value="{{ old('name', $user->name) }}" 
                           required
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Email *</label>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email', $user->email) }}" 
                           required
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Téléphone</label>
                    <input type="tel" 
                           name="phone" 
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="+224 XXX XXX XXX"
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Delivery Address --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Adresse de livraison</label>
                    <textarea name="delivery_address" 
                              rows="3"
                              placeholder="Ex: Quartier Dixinn, Commune de Dixinn, Conakry"
                              class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500 @error('delivery_address') border-red-500 @enderror">{{ old('delivery_address', $user->delivery_address) }}</textarea>
                    @error('delivery_address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-600 mt-1">Cette adresse sera utilisée par défaut pour vos commandes</p>
                </div>

                {{-- Submit Buttons --}}
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-yellow-600 text-white py-3 rounded-lg hover:bg-yellow-700 transition font-semibold">
                        Enregistrer les modifications
                    </button>
                    <a href="{{ route('client.profile.index') }}" 
                       class="flex-1 text-center bg-gray-100 text-gray-700 py-3 rounded-lg hover:bg-gray-200 transition font-semibold">
                        Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Change Password Section --}}
    <div class="bg-white rounded-xl shadow mt-6">
        <div class="p-6 border-b">
            <h2 class="text-xl font-semibold">Changer le mot de passe</h2>
        </div>

        <form action="{{ route('client.profile.password') }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Mot de passe actuel</label>
                    <input type="password" 
                           name="current_password" 
                           required
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Nouveau mot de passe</label>
                    <input type="password" 
                           name="password" 
                           required
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Confirmer le nouveau mot de passe</label>
                    <input type="password" 
                           name="password_confirmation" 
                           required
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:border-yellow-500">
                </div>

                <button type="submit" 
                        class="w-full bg-yellow-600 text-white py-3 rounded-lg hover:bg-yellow-700 transition font-semibold">
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>
</div>
@endsection