{{-- resources/views/vendeur/products/edit.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Modifier un produit - Espace Vendeur')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- En-tête --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Modifier un produit</h1>
        <p class="text-yellow-600 mt-1">Mettez à jour les informations de votre produit</p>
    </div>

    {{-- Formulaire --}}
    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('vendeur.products.update', $product) }}" method="POST" enctype="multipart/form-data"
              x-data="productForm({{ $product->toJson() }})">
            @csrf
            @method('PATCH')
            
            <div class="p-6 space-y-6">
                {{-- Informations générales --}}
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Nom du produit --}}
                        <div class="md:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom du produit <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $product->name) }}"
                                   x-model="name"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                   placeholder="Ex: Téléphone iPhone 15">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Catégorie --}}
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Catégorie <span class="text-red-500">*</span>
                            </label>
                            <select id="category_id" 
                                    name="category_id" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                                <option value="">Sélectionnez une catégorie</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Statut --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Statut
                            </label>
                            <select id="status" 
                                    name="status" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                                <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>
                                    Actif
                                </option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                    Inactif
                                </option>
                            </select>
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description <span class="text-red-500">*</span>
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="4" 
                                      required
                                      x-model="description"
                                      @input="updateDescription()"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                      placeholder="Décrivez votre produit en détail...">{{ old('description', $product->description) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">
                                <span x-text="description.length">0</span> / 2000 caractères
                            </p>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Prix et stock --}}
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Prix et stock</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Prix --}}
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                Prix (GNF) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       value="{{ old('price', $product->price) }}"
                                       required
                                       step="0.01"
                                       min="0"
                                       class="w-full px-3 py-2 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                       placeholder="0.00">
                                <span class="absolute left-3 top-2.5 text-gray-500">GNF</span>
                            </div>
                            @error('price')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Stock --}}
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">
                                Quantité en stock <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="stock" 
                                   name="stock" 
                                   value="{{ old('stock', $product->stock) }}"
                                   required
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                   placeholder="0">
                            @error('stock')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Image --}}
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Image du produit</h3>
                    
                    <div class="flex items-start space-x-6">
                        {{-- Image actuelle --}}
                        <div class="flex-shrink-0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Image actuelle
                            </label>
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" 
                                     alt="{{ $product->name }}" 
                                     class="h-32 w-32 object-cover rounded-lg shadow">
                            @else
                                <div class="h-32 w-32 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-2xl"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Nouvelle image --}}
                        <div class="flex-1">
                            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                                Changer l'image
                            </label>
                            <div class="flex items-center justify-center w-full">
                                <label for="image" 
                                       class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6" x-show="!imagePreview">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Cliquez pour uploader</span> ou glissez-déposez
                                        </p>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF (MAX. 2Mo)</p>
                                        <p class="text-xs text-gray-400 mt-2">Laissez vide pour conserver l'image actuelle</p>
                                    </div>
                                    <div id="imagePreview" x-show="imagePreview" class="hidden">
                                        <img :src="imagePreview" alt="Preview" class="h-48 w-48 object-cover rounded-lg">
                                        <button type="button" 
                                                @click="clearImage()"
                                                class="mt-2 text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-times mr-1"></i>Supprimer la nouvelle image
                                        </button>
                                    </div>
                                    <input id="image" 
                                           type="file" 
                                           name="image" 
                                           accept="image/*"
                                           @change="handleImageUpload($event)"
                                           class="hidden">
                                </label>
                            </div>
                            @error('image')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="bg-gray-50 px-6 py-4 flex justify-between rounded-b-lg">
                <div class="flex space-x-2">
                    <a href="{{ route('vendeur.products.show', $product) }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-eye mr-2"></i>Voir
                    </a>
                    <button type="button" 
                            x-data="{ 
                                productId: {{ $product->id }},
                                productName: '{{ $product->name }}',
                                showModal: false 
                            }"
                            @click="showModal = true"
                            class="px-4 py-2 border border-red-300 rounded-lg text-red-600 hover:bg-red-50 transition">
                        <i class="fas fa-trash mr-2"></i>Supprimer
                    </button>
                </div>
                
                <div class="flex space-x-4">
                    <a href="{{ route('vendeur.products.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        Annuler
                    </a>
                    <button type="submit" 
                            :disabled="isSubmitting"
                            class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting">
                            <i class="fas fa-save mr-2"></i>Mettre à jour
                        </span>
                        <span x-show="isSubmitting" class="flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Mise à jour...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal de confirmation suppression --}}
<div x-data="{ showDeleteModal: false, deleteUrl: '', deleteName: '' }" 
     x-show="showDeleteModal" 
     x-cloak
     class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
     style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 text-center">
                Confirmer la suppression
            </h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 text-center">
                    Êtes-vous sûr de vouloir supprimer le produit <span x-text="deleteName" class="font-semibold"></span> ?
                    <br>Cette action est irréversible.
                </p>
            </div>
            <div class="flex justify-center space-x-4 mt-4">
                <button @click="showDeleteModal = false" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                    Annuler
                </button>
                <form :action="deleteUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productForm(product) {
    return {
        imagePreview: null,
        isSubmitting: false,
        name: product.name,
        description: product.description,
        
        init() {
            // Initialiser les compteurs
            this.updateDescription();
        },
        
        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('L\'image ne doit pas dépasser 2Mo', 'error');
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                    document.querySelector('#imagePreview').classList.remove('hidden');
                    document.querySelector('[x-show="!imagePreview"]').classList.add('hidden');
                };
                reader.readAsDataURL(file);
            }
        },
        
        clearImage() {
            this.imagePreview = null;
            document.querySelector('#image').value = '';
            document.querySelector('#imagePreview').classList.add('hidden');
            document.querySelector('[x-show="!imagePreview"]').classList.remove('hidden');
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
