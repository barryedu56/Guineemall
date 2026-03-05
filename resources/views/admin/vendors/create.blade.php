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
