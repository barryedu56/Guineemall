{{-- resources/views/vendeur/products/show.blade.php --}}
@extends('vendeur.layouts.app')

@section('title', 'Détails du produit - ' . $product->name)

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- En-tête avec actions --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
            <p class="text-gray-600 mt-1">Détails et informations du produit</p>
        </div>
        
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="{{ route('vendeur.products.edit', $product) }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fas fa-edit"></i>
                <span>Modifier</span>
            </a>
            <button x-data="{ 
                productId: {{ $product->id }},
                productName: '{{ $product->name }}',
                showModal: false 
            }"
                    @click="showModal = true"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center space-x-2">
                <i class="fas fa-trash"></i>
                <span>Supprimer</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Image et informations principales --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Image du produit --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Image du produit</h3>
                <div class="flex justify-center">
                    @if($product->image_url)
                        <img src="{{ $product->image_url }}" 
                             alt="{{ $product->name }}" 
                             class="max-w-full h-auto rounded-lg shadow-md max-h-96 object-contain">
                    @else
                        <div class="max-w-full h-96 bg-gray-100 rounded-lg shadow-md flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Description --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Description</h3>
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $product->description }}</p>
                </div>
            </div>

            {{-- Statistiques --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiques</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $product->orderItems->count() }}</div>
                        <div class="text-sm text-gray-600">Ventes totales</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $product->stock }}</div>
                        <div class="text-sm text-gray-600">Stock actuel</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ $product->formatted_price }}</div>
                        <div class="text-sm text-gray-600">Prix unitaire</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">
                            {{ $product->orderItems->sum('quantity') }}
                        </div>
                        <div class="text-sm text-gray-600">Quantité vendue</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Informations détaillées --}}
        <div class="space-y-6">
            {{-- Informations générales --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Référence</label>
                        <p class="text-gray-900">#{{ $product->id }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Slug</label>
                        <p class="text-gray-900 text-sm">{{ $product->slug }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Catégorie</label>
                        <p class="text-gray-900">{{ $product->category->name ?? 'Non catégorisé' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Statut</label>
                        <div class="mt-1">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($product->status === 'active') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ $product->status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Prix et stock --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Prix et stock</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Prix</label>
                        <p class="text-2xl font-bold text-yellow-600">{{ $product->formatted_price }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Stock disponible</label>
                        <div class="flex items-center">
                            <p class="text-xl font-semibold 
                                @if($product->isOutOfStock()) text-red-600
                                @elseif($product->isLowStock()) text-yellow-600
                                @else text-yellow-600
                                @endif">
                                {{ $product->stock }} unités
                            </p>
                            @if($product->isLowStock())
                                <i class="fas fa-exclamation-triangle text-yellow-500 ml-2" title="Stock bas"></i>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">État du stock</label>
                        <div class="mt-1">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($product->isOutOfStock()) bg-red-100 text-red-800
                                @elseif($product->isLowStock()) bg-yellow-100 text-yellow-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                @if($product->isOutOfStock()) Rupture de stock
                                @elseif($product->isLowStock()) Stock bas
                                @else En stock
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Actions rapides</h3>
                <div class="space-y-3">
                    <button x-data="{ 
                        productId: {{ $product->id }},
                        currentStatus: '{{ $product->status }}',
                        isProcessing: false 
                    }"
                            @click="toggleProductStatus(productId)"
                            :disabled="isProcessing"
                            class="w-full px-4 py-2 border rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed
                                @if($product->status === 'active') border-yellow-300 text-yellow-700 hover:bg-yellow-50
                                @else border-yellow-300 text-yellow-700 hover:bg-yellow-50
                                @endif">
                        <span x-show="!isProcessing" class="flex items-center justify-center">
                            <i class="fas mr-2" 
                               :class="currentStatus === 'active' ? 'fa-toggle-off' : 'fa-toggle-on'"></i>
                            {{ $product->status === 'active' ? 'Désactiver le produit' : 'Activer le produit' }}
                        </span>
                        <span x-show="isProcessing" class="flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Traitement...
                        </span>
                    </button>

                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('vendeur.products.edit', $product) }}" 
                           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                        <button x-data="{ 
                            productId: {{ $product->id }},
                            productName: '{{ $product->name }}',
                            showModal: false 
                        }"
                                @click="showModal = true"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-trash mr-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>

            {{-- Informations système --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations système</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Créé le:</span>
                        <span class="text-gray-900">{{ $product->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Modifié le:</span>
                        <span class="text-gray-900">{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Vendeur:</span>
                        <span class="text-gray-900">{{ auth()->user()->vendor->shop_name }}</span>
                    </div>
                </div>
            </div>
        </div>
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
// Fonction pour toggle le statut d'un produit
function toggleProductStatus(productId) {
    const button = event.currentTarget;
    const icon = button.querySelector('i');
    
    button.disabled = true;
    button.classList.add('opacity-50');
    
    fetch(`/vendeur/products/${productId}/toggle-status`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour l'icône et le texte
            if (data.status === 'active') {
                icon.classList.remove('fa-toggle-off');
                icon.classList.add('fa-toggle-on');
                button.innerHTML = '<i class="fas fa-toggle-on mr-2"></i>Désactiver le produit';
            } else {
                icon.classList.remove('fa-toggle-on');
                icon.classList.add('fa-toggle-off');
                button.innerHTML = '<i class="fas fa-toggle-off mr-2"></i>Activer le produit';
            }
            
            // Afficher message de succès
            showNotification(data.message, 'success');
            
            // Recharger la page après un court délai pour voir les changements
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Erreur lors de la mise à jour du statut', 'error');
    })
    .finally(() => {
        button.disabled = false;
        button.classList.remove('opacity-50');
    });
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
