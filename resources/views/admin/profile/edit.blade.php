@extends('admin.layouts.app')

@section('title', 'Profil Administrateur')
@section('page-title', 'Profil')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profile-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Forcer la méthode PATCH
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PATCH';
            form.appendChild(methodInput);
            
            console.log('Form submitted with PATCH method');
        });
    }
});
</script>
@endpush

@push('meta')
<meta name="cache-control" content="no-cache, no-store, must-revalidate">
<meta name="pragma" content="no-cache">
<meta name="expires" content="0">
@endpush

@section('content')
<div class="slide-in">
    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Error Messages -->
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <div class="flex items-center mb-2">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Veuillez corriger les erreurs suivantes :
            </div>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Header -->
    <div class="admin-card mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Profil Administrateur</h2>
                <p class="text-sm text-gray-600 mt-1">Gérez vos informations personnelles.</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="admin-badge admin-badge-admin">Admin</span>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="admin-card mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Informations du Profil</h3>
        
        <form id="profile-form" method="POST" action="{{ route('admin.profile.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" value="PATCH">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom Complet
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ auth()->user()->name }}"
                        class="admin-input"
                        required
                    />
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Adresse Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ auth()->user()->email }}"
                        class="admin-input"
                        required
                    />
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Téléphone
                </label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    value="{{ auth()->user()->phone ?? '' }}"
                    class="admin-input"
                    placeholder="+224 XXX XXX XXX"
                />
                @error('phone')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                    Adresse
                </label>
                <textarea 
                    id="address" 
                    name="address" 
                    rows="3"
                    class="admin-input"
                    placeholder="Votre adresse complète"
                >{{ auth()->user()->address ?? '' }}</textarea>
                @error('address')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.dashboard') }}" class="admin-btn admin-btn-secondary">
                    Annuler
                </a>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i>
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>

    <!-- Password Change -->
    <div class="admin-card mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Changer le Mot de Passe</h3>
        
        <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')
            
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Mot de passe actuel
                </label>
                <input 
                    type="password" 
                    id="current_password" 
                    name="current_password" 
                    class="admin-input"
                    required
                />
                @error('current_password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Nouveau mot de passe
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="admin-input"
                        required
                    />
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirmer le mot de passe
                    </label>
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        class="admin-input"
                        required
                    />
                    @error('password_confirmation')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-key"></i>
                    Changer le mot de passe
                </button>
            </div>
        </form>
    </div>

    <!-- Account Actions -->
    <div class="admin-card">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Actions du Compte</h3>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-shield text-orange-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Supprimer le compte</p>
                        <p class="text-xs text-gray-500">Cette action est irréversible</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.profile.destroy') }}" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn-danger">
                        <i class="fas fa-trash"></i>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
