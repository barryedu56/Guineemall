{{-- resources/views/vendeur/profile/edit.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Modifier ma boutique - Espace Vendeur')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Modifier ma boutique</h1>
        <p class="text-gray-600 mt-1">Mettez à jour les informations de votre boutique</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Formulaire principal --}}
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <form action="{{ route('vendeur.profile.update') }}" method="POST" enctype="multipart/form-data"
                      x-data="profileForm({{ $vendor->toJson() ?? '{}' }})">
                    @csrf
                    @method('PATCH')
                    
                    <div class="p-6 space-y-6">
                        {{-- Informations boutique --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations boutique</h3>
                            
                            <div class="space-y-4">
                                {{-- Nom de la boutique --}}
                                <div>
                                    <label for="shop_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nom de la boutique <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="shop_name" 
                                           name="shop_name" 
                                           value="{{ old('shop_name', $vendor->shop_name ?? '') }}"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                           placeholder="Ma Boutique">
                                    @error('shop_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea id="description" 
                                              name="description" 
                                              rows="4" 
                                              x-model="description"
                                              @input="updateDescription()"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                              placeholder="Décrivez votre boutique...">{{ old('description', $vendor->description ?? '') }}</textarea>
                                    <p class="mt-1 text-sm text-gray-500">
                                        <span x-text="description.length">0</span> / 2000 caractères
                                    </p>
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Logo --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Logo de la boutique</h3>
                            
                            <div class="flex items-start space-x-6">
                                {{-- Logo actuel --}}
                                <div class="flex-shrink-0">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Logo actuel
                                    </label>
                                    @if($vendor && $vendor->image_url)
                                        <img src="{{ $vendor->image_url }}" 
                                             alt="{{ $vendor->shop_name }}" 
                                             class="h-24 w-24 object-cover rounded-lg shadow">
                                    @else
                                        <div class="h-24 w-24 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-store text-gray-400 text-2xl"></i>
                                        </div>
                                    @endif
                                </div>

                                {{-- Nouveau logo --}}
                                <div class="flex-1">
                                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">
                                        Changer le logo
                                    </label>
                                    <div class="flex items-center justify-center w-full">
                                        <label for="logo" 
                                               class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                            <div class="flex flex-col items-center justify-center pt-5 pb-6" x-show="!logoPreview">
                                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                                <p class="mb-2 text-sm text-gray-500">
                                                    <span class="font-semibold">Cliquez pour uploader</span> ou glissez-déposez
                                                </p>
                                                <p class="text-xs text-gray-500">PNG, JPG, GIF (MAX. 2Mo)</p>
                                                <p class="text-xs text-gray-400 mt-2">Laissez vide pour conserver le logo actuel</p>
                                            </div>
                                            <div id="logoPreview" x-show="logoPreview" class="hidden">
                                                <img :src="logoPreview" alt="Preview" class="h-32 w-32 object-cover rounded-lg">
                                                <button type="button" 
                                                        @click="clearLogo()"
                                                        class="mt-2 text-red-600 hover:text-red-800 text-sm">
                                                    <i class="fas fa-times mr-1"></i>Supprimer le nouveau logo
                                                </button>
                                            </div>
                                            <input id="logo" 
                                                   type="file" 
                                                   name="logo" 
                                                   accept="image/*"
                                                   @change="handleLogoUpload($event)"
                                                   class="hidden">
                                        </label>
                                    </div>
                                    @error('logo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Informations personnelles --}}
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Informations personnelles</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Téléphone --}}
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Téléphone
                                    </label>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', auth()->user()->phone ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                           placeholder="+224 XXX XX XX XX">
                                    @error('phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Adresse --}}
                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                        Adresse
                                    </label>
                                    <input type="text" 
                                           id="address" 
                                           name="address" 
                                           value="{{ old('address', auth()->user()->delivery_address ?? '') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                           placeholder="Votre adresse complète">
                                    @error('address')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-4 rounded-b-lg">
                        <a href="{{ route('vendeur.profile.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                            Annuler
                        </a>
                        <button type="submit" 
                                :disabled="isSubmitting"
                                class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting">
                                <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                            </span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar actions -->
        <div class="space-y-6">
            <!-- Actions rapides -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Actions rapides</h3>
                <div class="space-y-3">
                    <a href="{{ route('vendeur.profile.password.edit') }}" 
                       class="w-full px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center justify-center space-x-2">
                        <i class="fas fa-key"></i>
                        <span>Changer mot de passe</span>
                    </a>
                    
                    @if($vendor && $vendor->logo)
                        <form action="{{ route('vendeur.profile.delete-logo') }}" method="POST"
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer votre logo ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-full px-4 py-3 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition flex items-center justify-center space-x-2">
                                <i class="fas fa-trash"></i>
                                <span>Supprimer le logo</span>
                            </button>
                        </form>
                    @endif
                    
                    <a href="{{ route('vendeur.profile.index') }}" 
                       class="w-full px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center justify-center space-x-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Retour au profil</span>
                    </a>
                </div>
            </div>

            <!-- Informations système -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations système</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">ID Utilisateur:</span>
                        <span class="text-gray-900">#{{ auth()->id() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Email:</span>
                        <span class="text-gray-900">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Rôle:</span>
                        <span class="text-gray-900">Vendeur</span>
                    </div>
                    @if($vendor)
                        <div class="flex justify-between">
                            <span class="text-gray-500">ID Boutique:</span>
                            <span class="text-gray-900">#{{ $vendor->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Statut:</span>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($vendor->status === 'approved') bg-yellow-100 text-yellow-800
                                @elseif($vendor->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                @if($vendor->status === 'approved') Approuvée
                                @elseif($vendor->status === 'pending') En attente
                                @else Suspendue
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profileForm(vendor) {
    return {
        logoPreview: null,
        isSubmitting: false,
        description: vendor.description || '',
        
        init() {
            this.updateDescription();
        },
        
        handleLogoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('Le logo ne doit pas dépasser 2Mo', 'error');
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.logoPreview = e.target.result;
                    document.querySelector('#logoPreview').classList.remove('hidden');
                    document.querySelector('[x-show="!logoPreview"]').classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }
        },
        
        clearLogo() {
            this.logoPreview = null;
            document.querySelector('#logo').value = '';
            document.querySelector('#logoPreview').classList.add('hidden');
            document.querySelector('[x-show="!logoPreview"]').classList.remove('hidden');
        },
        
        updateDescription() {
            this.description = document.querySelector('#description').value;
        }
    };
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full ${
        type === 'success' ? 'bg-yellow-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);
    
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>
@endpush
