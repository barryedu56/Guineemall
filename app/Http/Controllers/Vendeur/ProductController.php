<?php

namespace App\Http\Controllers\Vendeur;

use App\Http\Controllers\Controller;
use App\Services\Vendeur\ProductService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Product;
use App\Http\Requests\Vendeur\StoreProductRequest;
use App\Http\Requests\Vendeur\UpdateProductRequest;

class ProductController extends Controller
{
    protected ProductService $productService;
    
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }
    
    /**
     * Afficher la liste des produits du vendeur
     */
    public function index(Request $request): View
    {
        $vendorId = auth()->user()->vendor->id;
        
        $filters = [
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'),
            'search' => $request->get('search'),
            'stock_status' => $request->get('stock_status'),
        ];
        
        $products = $this->productService->getVendorProducts($vendorId, $filters);
        $categories = $this->productService->getCategories();
        
        // Calculer les statistiques de stock
        $lowStockProducts = Product::where('vendor_id', $vendorId)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 5)
            ->count();
            
        $outOfStockProducts = Product::where('vendor_id', $vendorId)
            ->where('stock', '<=', 0)
            ->count();
        
        return view('vendeur.products.index', compact('products', 'categories', 'filters', 'lowStockProducts', 'outOfStockProducts'));
    }
    
    /**
     * Afficher le formulaire de création de produit
     */
    public function create(): View
    {
        try {
            // Récupérer directement les catégories actives
            $categories = \App\Models\Category::where('status', 'active')
                ->orderBy('name')
                ->get();
            
            // Debug forcer l'affichage
            \Log::info('Categories trouvées: ' . $categories->count());
            
            // Si aucune catégorie, en créer une par défaut
            if ($categories->isEmpty()) {
                \Log::warning('Aucune catégorie trouvée, création d\'une catégorie par défaut');
                
                $defaultCategory = \App\Models\Category::create([
                    'name' => 'Général',
                    'slug' => 'general',
                    'description' => 'Catégorie par défaut',
                    'status' => 'active',
                ]);
                
                $categories = \App\Models\Category::where('status', 'active')
                    ->orderBy('name')
                    ->get();
            }
            
            // Passer les catégories à la vue
            return view('vendeur.products.create', [
                'categories' => $categories,
                'debug' => [
                    'count' => $categories->count(),
                    'categories' => $categories->toArray(),
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erreur dans ProductController@create: ' . $e->getMessage());
            
            // Créer une catégorie de secours
            try {
                $emergencyCategory = \App\Models\Category::firstOrCreate([
                    'slug' => 'emergency'
                ], [
                    'name' => 'Divers',
                    'description' => 'Catégorie d\'urgence',
                    'status' => 'active',
                ]);
                
                $categories = collect([$emergencyCategory]);
                
                return view('vendeur.products.create', compact('categories'));
                
            } catch (\Exception $e2) {
                \Log::error('Erreur critique: ' . $e2->getMessage());
                
                // Dernier recours: passer une collection vide avec message
                return view('vendeur.products.create', [
                    'categories' => collect([]),
                    'error' => 'Impossible de charger les catégories. Veuillez contacter l\'administrateur.'
                ]);
            }
        }
    }
    
    /**
     * Enregistrer un nouveau produit
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        try {
            $vendorId = auth()->user()->vendor->id;
            $image = $request->file('image');
            
            $product = $this->productService->createProduct(
                $request->validated(),
                $image,
                $vendorId
            );
            
            return redirect()
                ->route('vendeur.products.index')
                ->with('success', 'Produit créé avec succès.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création du produit: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher les détails d'un produit
     */
    public function show(Product $product): View
    {
        $this->authorize('view', $product);
        
        return view('vendeur.products.show', compact('product'));
    }
    
    /**
     * Afficher le formulaire d'édition de produit
     */
    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        
        $categories = $this->productService->getCategories();
        return view('vendeur.products.edit', compact('product', 'categories'));
    }
    
    /**
     * Mettre à jour un produit
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->authorize('update', $product);
            $image = $request->file('image');
            
            $this->productService->updateProduct(
                $product,
                $request->validated(),
                $image
            );
            
            return redirect()
                ->route('vendeur.products.index')
                ->with('success', 'Produit mis à jour avec succès.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour du produit: ' . $e->getMessage());
        }
    }
    
    /**
     * Supprimer un produit
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->authorize('delete', $product);
            $this->productService->deleteProduct($product);
            
            return redirect()
                ->route('vendeur.products.index')
                ->with('success', 'Produit supprimé avec succès.');
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression du produit: ' . $e->getMessage());
        }
    }
    
    /**
     * Activer/désactiver un produit
     */
    public function toggleStatus(Product $product): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $updatedProduct = $this->productService->toggleProductStatus($product);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'status' => $updatedProduct->status,
                    'message' => $updatedProduct->status === 'active'
                        ? 'Produit activé avec succès.'
                        : 'Produit désactivé avec succès.',
                ]);
            }

            $statusLabel = $updatedProduct->status === 'active' ? 'activé' : 'désactivé';

            return redirect()
                ->back()
                ->with('success', "Produit {$statusLabel} avec succès.");
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour du statut: ' . $e->getMessage(),
                ], 400);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour du statut: ' . $e->getMessage());
        }
    }
    
    /**
     * Mettre à jour le stock (AJAX)
     */
    public function updateStock(Request $request, Product $product)
    {
        try {
            $request->validate([
                'stock' => 'required|integer|min:0'
            ]);
            
            $this->productService->updateStock($product, $request->stock);
            
            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour avec succès.',
                'stock' => $product->fresh()->stock
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du stock: ' . $e->getMessage()
            ], 400);
        }
    }
}
