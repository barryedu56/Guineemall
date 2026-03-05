@extends('vendeur.layouts.app')

@section('title', 'Ajouter un produit - Espace Vendeur')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header avec progression -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-black text-gray-800">
                    <i class="fas fa-plus-circle text-yellow-600 mr-3"></i>
                    Ajouter un produit
                </h1>
                <p class="text-gray-600 mt-2">Remplissez les informations pour ajouter un nouveau produit à votre catalogue</p>
            </div>
            <a href="{{ route('vendeur.products.index') }}" class="text-yellow-600 hover:text-yellow-700 font-bold">
                <i class="fas fa-arrow-left mr-2"></i>Retour aux produits
            </a>
        </div>
        
        <!-- Progression -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold">1</div>
                    <div>
                        <p class="font-bold text-gray-800">Informations générales</p>
                        <p class="text-xs text-gray-500">Nom, description, catégorie</p>
                    </div>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold">2</div>
                    <div>
                        <p class="font-bold text-gray-400">Prix et stock</p>
                        <p class="text-xs text-gray-400">Tarification et inventaire</p>
                    </div>
                </div>
                <div class="flex-1 h-1 bg-gray-200 mx-4"></div>
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold">3</div>
                    <div>
                        <p class="font-bold text-gray-400">Image</p>
                        <p class="text-xs text-gray-400">Photo du produit</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire moderne -->
    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <form action="{{ route('vendeur.products.store') }}" method="POST" enctype="multipart/form-data"
              x-data="productForm()" class="divide-y divide-gray-100">
            @csrf
            
            <!-- Section 1: Informations générales -->
            <div class="p-8">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-info-circle text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Informations générales</h3>
                        <p class="text-sm text-gray-500">Détails principaux du produit</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Nom du produit -->
                    <div class="lg:col-span-2">
                        <label for="name" class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-tag text-yellow-600 mr-2"></i>
                            Nom du produit <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               required
                               maxlength="255"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all text-lg"
                               placeholder="Ex: Téléphone iPhone 15 Pro Max">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Catégorie -->
                    <div>
                        <label for="category_id" class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-layer-group text-yellow-600 mr-2"></i>
                            Catégorie <span class="text-red-500">*</span>
                        </label>
                        
                        @php
                            $hasCategories = isset($categories) && $categories->isNotEmpty();
                        @endphp
                        
                        <select id="category_id" 
                                name="category_id" 
                                @if($hasCategories) required @endif
                                @if(! $hasCategories) disabled @endif
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                            <option value="">
                                @if($hasCategories)
                                    🏷️ Sélectionnez une catégorie
                                @else
                                    ❌ Aucune catégorie disponible
                                @endif
                            </option>
                            @if($hasCategories)
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        📦 {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        
                        @if(!$hasCategories)
                            <div class="mt-3 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Aucune catégorie active n'est disponible. Veuillez contacter un administrateur.
                                </p>
                            </div>
                        @endif
                        
                        @error('category_id')
                            <p class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Statut -->
                    <div>
                        <label for="status" class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-toggle-on text-yellow-600 mr-2"></i>
                            Statut de publication
                        </label>
                        <div class="flex space-x-4">
                            <label class="flex-1">
                                <input type="radio" name="status" value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }} 
                                       class="sr-only peer">
                                <div class="p-4 border-2 rounded-xl cursor-pointer peer-checked:border-yellow-500 peer-checked:bg-yellow-50 transition-all">
                                    <div class="flex items-center">
                                        <i class="fas fa-eye text-yellow-600 mr-3"></i>
                                        <div>
                                            <p class="font-bold text-gray-800">Actif</p>
                                            <p class="text-xs text-gray-500">Visible immédiatement</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="status" value="inactive" {{ old('status') == 'inactive' ? 'checked' : '' }} 
                                       class="sr-only peer">
                                <div class="p-4 border-2 rounded-xl cursor-pointer peer-checked:border-gray-500 peer-checked:bg-gray-50 transition-all">
                                    <div class="flex items-center">
                                        <i class="fas fa-eye-slash text-gray-600 mr-3"></i>
                                        <div>
                                            <p class="font-bold text-gray-800">Inactif</p>
                                            <p class="text-xs text-gray-500">Masqué des clients</p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="lg:col-span-2">
                        <label for="description" class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-align-left text-yellow-600 mr-2"></i>
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="6" 
                                  required
                                  maxlength="2000"
                                  x-model="description"
                                  @input="updateDescription()"
                                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all resize-none"
                                  placeholder="Décrivez votre produit en détail...">{{ old('description') }}</textarea>
                        <div class="flex justify-between items-center mt-2">
                            <p class="text-sm text-gray-500">
                                <span class="font-bold" :class="description.length > 1800 ? 'text-red-600' : 'text-gray-600'" x-text="description.length"></span> / 2000 caractères
                            </p>
                            <div class="flex space-x-2">
                                <button type="button" @click="description = ''" class="text-sm text-red-600 hover:text-red-800">
                                    <i class="fas fa-eraser mr-1"></i>Vider
                                </button>
                            </div>
                        </div>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Prix et stock -->
            <div class="p-8 bg-gray-50">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-dollar-sign text-orange-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Prix et stock</h3>
                        <p class="text-sm text-gray-500">Tarification et gestion d'inventaire</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Prix -->
                    <div>
                        <label for="price" class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-coins text-yellow-600 mr-2"></i>
                            Prix (GNF) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   value="{{ old('price') }}"
                                   required
                                   step="0.01"
                                   min="0"
                                   max="999999.99"
                                   class="w-full px-4 py-3 pl-16 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all text-lg font-bold"
                                   placeholder="0.00">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">GNF</span>
                        </div>
                        <div class="mt-2 flex space-x-2">
                            <button type="button" @click="document.getElementById('price').value = 1000" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">1K</button>
                            <button type="button" @click="document.getElementById('price').value = 5000" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">5K</button>
                            <button type="button" @click="document.getElementById('price').value = 10000" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">10K</button>
                            <button type="button" @click="document.getElementById('price').value = 50000" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">50K</button>
                            <button type="button" @click="document.getElementById('price').value = 100000" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">100K</button>
                        </div>
                        @error('price')
                            <p class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Stock -->
                    <div>
                        <label for="stock" class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-boxes text-yellow-600 mr-2"></i>
                            Quantité en stock <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="stock" 
                                   name="stock" 
                                   value="{{ old('stock', 1) }}"
                                   required
                                   min="0"
                                   max="99999"
                                   class="w-full px-4 py-3 pr-16 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all text-lg font-bold"
                                   placeholder="0">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2">
                                <i class="fas fa-cubes text-gray-400"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex space-x-2">
                            <button type="button" @click="document.getElementById('stock').value = 1" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">1</button>
                            <button type="button" @click="document.getElementById('stock').value = 5" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">5</button>
                            <button type="button" @click="document.getElementById('stock').value = 10" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">10</button>
                            <button type="button" @click="document.getElementById('stock').value = 25" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">25</button>
                            <button type="button" @click="document.getElementById('stock').value = 50" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">50</button>
                            <button type="button" @click="document.getElementById('stock').value = 100" class="text-xs px-3 py-1 bg-gray-200 rounded-lg hover:bg-gray-300">100</button>
                        </div>
                        @error('stock')
                            <p class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Image -->
            <div class="p-8">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-image text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Image du produit</h3>
                        <p class="text-sm text-gray-500">Photo principale pour attirer les clients</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-8">
                    <div class="flex-1">
                        <label for="image" class="block text-sm font-bold text-gray-700 mb-2">
                            <i class="fas fa-camera text-yellow-600 mr-2"></i>
                            Photo du produit
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label for="image" 
                                   class="flex flex-col items-center justify-center w-full h-80 border-2 border-dashed rounded-2xl cursor-pointer transition-all
                                          @class="imagePreview ? 'border-yellow-500 bg-yellow-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
                                   @dragover.prevent="$el.classList.add('border-yellow-500', 'bg-yellow-50')"
                                   @dragleave.prevent="$el.classList.remove('border-yellow-500', 'bg-yellow-50')"
                                   @drop.prevent="handleDrop($event)">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6" x-show="!imagePreview" x-transition>
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-5xl mb-4"></i>
                                    <p class="mb-2 text-lg font-semibold text-gray-700">
                                        Cliquez pour uploader
                                    </p>
                                    <p class="text-sm text-gray-500">ou glissez-déposez votre image ici</p>
                                    <p class="text-xs text-gray-400 mt-2">PNG, JPG, GIF (MAX. 2Mo)</p>
                                </div>
                                <div x-show="imagePreview" x-transition class="relative">
                                    <img :src="imagePreview" alt="Preview" class="h-64 w-64 object-cover rounded-2xl shadow-lg">
                                    <button type="button" 
                                            @click="clearImage()"
                                            class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full hover:bg-red-600 transition-all">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="mt-3 text-center">
                                        <p class="text-sm text-yellow-600 font-bold">
                                            <i class="fas fa-check-circle mr-2"></i>Image prête
                                        </p>
                                    </div>
                                </div>
                                <input id="image" 
                                       type="file" 
                                       name="image" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif"
                                       @change="handleImageUpload($event)"
                                       class="hidden">
                            </label>
                        </div>
                        @error('image')
                            <p class="mt-3 text-sm text-red-600 bg-red-50 p-3 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="bg-gradient-to-r from-gray-50 to-yellow-50 px-8 py-6 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2"></i>
                    Les champs marqués d'un * sont obligatoires
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('vendeur.products.index') }}" 
                       class="px-6 py-3 border-2 border-gray-300 rounded-xl text-gray-700 hover:bg-gray-100 transition-all font-bold">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </a>
                    <button type="submit" 
                            @if($categories->isEmpty()) disabled @endif
                            :disabled="isSubmitting"
                            class="px-8 py-3 vendor-gradient text-white rounded-xl hover:shadow-lg transition-all font-bold disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105">
                        <span x-show="!isSubmitting" class="flex items-center">
                            <i class="fas fa-save mr-3"></i>Ajouter le produit
                        </span>
                        <span x-show="isSubmitting" class="flex items-center">
                            <i class="fas fa-spinner fa-spin mr-3"></i>Enregistrement en cours...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productForm() {
    return {
        imagePreview: null,
        isSubmitting: false,
        description: '',
        
        init() {
            this.description = document.querySelector('#description').value;
        },
        
        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.processFile(file);
            }
        },
        
        handleDrop(event) {
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                this.processFile(files[0]);
            }
        },
        
        processFile(file) {
            // Vérifier le type de fichier
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                this.showNotification('L\'image doit être au format JPEG, PNG ou GIF', 'error');
                return;
            }
            
            // Vérifier la taille (2Mo)
            if (file.size > 2 * 1024 * 1024) {
                this.showNotification('L\'image ne doit pas dépasser 2Mo', 'error');
                return;
            }
            
            // Si tout est bon, afficher la preview
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
                this.showNotification('Image chargée avec succès', 'success');
            };
            reader.onerror = () => {
                this.showNotification('Erreur lors de la lecture de l\'image', 'error');
            };
            reader.readAsDataURL(file);
        },
        
        clearImage() {
            this.imagePreview = null;
            document.querySelector('#image').value = '';
        },
        
        updateDescription() {
            this.description = document.querySelector('#description').value;
        },
        
        showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-2xl shadow-2xl z-50 transform transition-all duration-300 translate-x-full ${
                type === 'success' ? 'bg-yellow-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-3 text-xl"></i>
                    <span class="font-medium">${message}</span>
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
    };
}

// Validation du formulaire avant soumission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50');
    });
});
</script>
@endpush
