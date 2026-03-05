{{-- resources/views/vendeur/profile/create.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Créer ma boutique - Espace Vendeur')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Créer ma boutique</h1>
        <p class="text-gray-600 mt-1">Configurez votre boutique pour commencer à vendre sur {{ config('app_name.name') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Formulaire principal --}}
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <form action="{{ route('vendeur.profile.store') }}" method="POST" enctype="multipart/form-data"
                      x-data="profileForm()">
                    @csrf
                    
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
                                           value="{{ old('shop_name') }}"
                                           required
                                           x-model="shopName"
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
                                              placeholder="Décrivez votre boutique, vos produits, votre expertise...">{{ old('description') }}</textarea>
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
                            
                            <div class="flex items-center justify-center w-full">
                                <label for="logo" 
                                       class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6" x-show="!logoPreview">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Cliquez pour uploader</span> ou glissez-déposez
                                        </p>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF (MAX. 2Mo)</p>
                                        <p class="text-xs text-gray-400 mt-2">Optionnel - vous pourrez l'ajouter plus tard</p>
                                    </div>
                                    <div id="logoPreview" x-show="logoPreview" class="hidden">
                                        <img :src="logoPreview" alt="Preview" class="h-32 w-32 object-cover rounded-lg">
                                        <button type="button" 
                                                @click="clearLogo()"
                                                class="mt-2 text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-times mr-1"></i>Supprimer le logo
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
                                           value="{{ old('phone') }}"
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
                                           value="{{ old('address') }}"
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
                        <a href="{{ route('vendeur.dashboard') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                            Annuler
                        </a>
                        <button type="submit" 
                                :disabled="isSubmitting"
                                class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!isSubmitting">
                                <i class="fas fa-store mr-2"></i>Créer ma boutique
                            </span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Création en cours...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar informations -->
        <div class="space-y-6">
            <!-- Guide -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Guide de création</h3>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-yellow-600 text-xs"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Nom unique</h4>
                            <p class="text-sm text-gray-600">Choisissez un nom qui représente votre marque</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-yellow-600 text-xs"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Description détaillée</h4>
                            <p class="text-sm text-gray-600">Présentez vos produits et votre savoir-faire</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-yellow-600 text-xs"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Logo professionnel</h4>
                            <p class="text-sm text-gray-600">Une image vaut mille mots</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-yellow-600 text-xs"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Coordonnées exactes</h4>
                            <p class="text-sm text-gray-600">Facilitez la communication avec vos clients</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Avantages -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pourquoi vendre sur {{ config('app_name.name') }} ?</h3>
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-users text-yellow-600"></i>
                        <span class="text-sm text-gray-700">Accès à des milliers de clients</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-shield-alt text-yellow-600"></i>
                        <span class="text-sm text-gray-700">Paiements sécurisés</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-chart-line text-yellow-600"></i>
                        <span class="text-sm text-gray-700">Statistiques détaillées</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-headset text-yellow-600"></i>
                        <span class="text-sm text-gray-700">Support client dédié</span>
                    </div>
                </div>
            </div>

            <!-- Validation -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <h3 class="text-lg font-medium text-blue-900 mb-3">Processus de validation</h3>
                <div class="space-y-2 text-sm text-blue-800">
                    <p>• Soumission de votre boutique</p>
                    <p>• Vérification par notre équipe (24-48h)</p>
                    <p>• Validation et activation de votre compte</p>
                    <p>• Commencez à vendre immédiatement</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function profileForm() {
    return {
        logoPreview: null,
        isSubmitting: false,
        shopName: '',
        description: '',
        
        init() {
            this.shopName = document.querySelector('#shop_name').value;
            this.description = document.querySelector('#description').value;
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
