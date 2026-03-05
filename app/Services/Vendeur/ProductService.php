<?php

namespace App\Services\Vendeur;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /**
     * Créer un nouveau produit pour un vendeur
     */
    public function createProduct(array $data, UploadedFile $image = null, int $vendorId): Product
    {
        $productData = [
            'vendor_id' => $vendorId,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . time(),
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'status' => $data['status'] ?? 'active',
        ];
        
        // Créer le produit d'abord
        $product = Product::create($productData);
        
        // Stocker l'image si fournie en utilisant le trait
        if ($image) {
            $product->storeImage($image);
        }
        
        return $product->fresh();
    }
    
    /**
     * Mettre à jour un produit
     */
    public function updateProduct(Product $product, array $data, UploadedFile $image = null): Product
    {
        // Vérifier que le produit appartient au vendeur
        $this->verifyProductOwnership($product);
        
        $productData = [
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'status' => $data['status'] ?? $product->status,
        ];
        
        // Mettre à jour le slug si le nom a changé
        if ($data['name'] !== $product->name) {
            $productData['slug'] = Str::slug($data['name']) . '-' . time();
        }
        
        // Mettre à jour les données du produit
        $product->update($productData);
        
        // Stocker la nouvelle image si fournie en utilisant le trait
        if ($image) {
            $product->storeImage($image);
        }
        
        return $product->fresh();
    }
    
    /**
     * Supprimer un produit
     */
    public function deleteProduct(Product $product): bool
    {
        $this->verifyProductOwnership($product);
        
        // Supprimer l'image en utilisant le trait
        $product->deleteImage();
        
        return $product->delete();
    }
    
    /**
     * Activer/désactiver un produit
     */
    public function toggleProductStatus(Product $product): Product
    {
        $this->verifyProductOwnership($product);
        
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $product->update(['status' => $newStatus]);
        
        return $product->fresh();
    }
    
    /**
     * Mettre à jour le stock d'un produit
     */
    public function updateStock(Product $product, int $quantity): Product
    {
        $this->verifyProductOwnership($product);
        
        $product->update(['stock' => $quantity]);
        return $product->fresh();
    }
    
    /**
     * Obtenir les produits d'un vendeur avec filtres
     */
    public function getVendorProducts(int $vendorId, array $filters = [])
    {
        $query = Product::where('vendor_id', $vendorId)
            ->with('category');
            
        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Filtre par catégorie
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        // Recherche
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Filtre par stock
        if (isset($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'out_of_stock':
                    $query->where('stock', '<=', 0);
                    break;
                case 'low_stock':
                    $query->where('stock', '>', 0)->where('stock', '<=', 5);
                    break;
                case 'in_stock':
                    $query->where('stock', '>', 5);
                    break;
            }
        }
        
        return $query->orderBy('created_at', 'desc')->paginate(12);
    }
    
    /**
     * Vérifier que le produit appartient au vendeur connecté
     */
    private function verifyProductOwnership(Product $product): void
    {
        if ($product->vendor_id !== auth()->user()->vendor->id) {
            abort(403, 'Ce produit ne vous appartient pas.');
        }
    }
    
    /**
     * Obtenir les catégories disponibles
     */
    public function getCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('categories_active', 600, function () {
            return Category::where('status', 'active')
                ->orderBy('name')
                ->get();
        });
    }
}
