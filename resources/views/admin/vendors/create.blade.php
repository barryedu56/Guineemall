@extends('admin.layouts.app')

@section('title', 'Créer un vendeur')
@section('page-title', 'Créer un vendeur')

@section('content')
<div class="slide-in">
    <!-- Header -->
    <div class="admin-card mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Créer un vendeur</h2>
                <p class="text-sm text-gray-600 mt-1">Ajouter un nouveau vendeur à la plateforme.</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.vendors.index') }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="admin-card">
        <form method="POST" action="{{ route('admin.vendors.store') }}" class="space-y-6">
            @csrf
            
            <!-- Informations de la boutique -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la boutique</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="shop_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom de la boutique *
                        </label>
                        <input 
                            type="text" 
                            id="shop_name" 
                            name="shop_name" 
                            value="{{ old('shop_name') }}"
                            class="admin-input"
                            required
                        />
                        @error('shop_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="shop_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea 
                            id="shop_description" 
                            name="shop_description" 
                            rows="3"
                            class="admin-input"
                        >{{ old('shop_description') }}</textarea>
                        @error('shop_description')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Informations du propriétaire -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du propriétaire</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nom du propriétaire *
                        </label>
                        <input 
                            type="text" 
                            id="owner_name" 
                            name="owner_name" 
                            value="{{ old('owner_name') }}"
                            class="admin-input"
                            required
                        />
                        @error('owner_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="owner_email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email du propriétaire *
                        </label>
                        <input 
                            type="email" 
                            id="owner_email" 
                            name="owner_email" 
                            value="{{ old('owner_email') }}"
                            class="admin-input"
                            required
                        />
                        @error('owner_email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Téléphone
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="{{ old('phone') }}"
                            class="admin-input"
                        />
                        @error('phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Adresse
                        </label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address" 
                            value="{{ old('address') }}"
                            class="admin-input"
                        />
                        @error('address')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Authentification -->
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Authentification</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Mot de passe *
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="admin-input"
                            required
                            placeholder="Définir un mot de passe sécurisé"
                        />
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500">
                            Minimum 8 caractères. Laissez vide pour générer automatiquement.
                        </p>
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmation du mot de passe *
                        </label>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="admin-input"
                            required
                            placeholder="Confirmer le mot de passe"
                        />
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <!-- Option de génération automatique -->
                <div class="mt-4">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="generate_password" 
                            name="generate_password" 
                            value="1"
                            class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded"
                            onchange="togglePasswordFields()"
                        >
                        <label for="generate_password" class="ml-2 block text-sm text-gray-700">
                            Générer automatiquement un mot de passe sécurisé
                        </label>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Si coché, un mot de passe sécurisé sera généré et envoyé au vendeur par email.
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.vendors.index') }}" class="admin-btn admin-btn-secondary">
                    Annuler
                </a>
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i>
                    Créer le vendeur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function togglePasswordFields() {
    const generateCheckbox = document.getElementById('generate_password');
    const passwordField = document.getElementById('password');
    const passwordConfirmField = document.getElementById('password_confirmation');
    
    if (generateCheckbox.checked) {
        // Désactiver les champs mot de passe si génération automatique
        passwordField.disabled = true;
        passwordField.required = false;
        passwordField.value = '';
        
        passwordConfirmField.disabled = true;
        passwordConfirmField.required = false;
        passwordConfirmField.value = '';
        
        // Ajouter une indication visuelle
        passwordField.placeholder = 'Sera généré automatiquement';
        passwordConfirmField.placeholder = 'Sera généré automatiquement';
    } else {
        // Réactiver les champs si génération manuelle
        passwordField.disabled = false;
        passwordField.required = true;
        passwordField.placeholder = 'Définir un mot de passe sécurisé';
        
        passwordConfirmField.disabled = false;
        passwordConfirmField.required = true;
        passwordConfirmField.placeholder = 'Confirmer le mot de passe';
    }
}

// Validation personnalisée pour le formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const generateCheckbox = document.getElementById('generate_password');
    
    form.addEventListener('submit', function(e) {
        if (!generateCheckbox.checked) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirmation').value;
            
            // Validation basique du mot de passe
            if (password.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return false;
            }
            
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
        }
    });
});
</script>
@endsection
