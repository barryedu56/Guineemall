<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    /**
     * Affiche la page d'accueil du catalogue avec tous les produits visibles
     */
    public function index(Request $request): View
    {
        $query = Product::where('status', 'active')
            ->with(['category', 'vendor']);

        // Filtrage par catégorie si spécifié
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Recherche par nom si spécifié
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(12);
        $categories = Cache::remember('categories_with_counts', 600, function () {
            return Category::query()
                ->withCount(['products' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->orderBy('name')
                ->get();
        });

        return view('client.catalog.index', compact('products', 'categories'));
    }

    /**
     * Affiche les détails d'un produit spécifique
     */
    public function show(Product $product): View
    {
        // Vérifier que le produit est visible pour les clients
        if ($product->status !== 'active') {
            abort(404);
        }

        // Charger les relations nécessaires
        $product->load(['category', 'vendor']);

        // Produits similaires (même catégorie)
        $similarProducts = Product::where('category_id', $product->category_id)
                                 ->where('id', '!=', $product->id)
                                 ->where('status', 'active')
                                 ->where('stock', '>', 0)
                                 ->with(['category', 'vendor'])
                                 ->take(4)
                                 ->get();

        return view('client.catalog.show', compact('product', 'similarProducts'));
    }

    /**
     * Affiche les produits par catégorie
     */
    public function category(Category $category): View
    {
        $products = Product::where('category_id', $category->id)
            ->where('status', 'active')
            ->with(['category', 'vendor'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $categories = Cache::remember('categories_with_counts', 600, function () {
            return Category::query()
                ->withCount(['products' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->orderBy('name')
                ->get();
        });

        return view('client.catalog.index', compact('products', 'categories', 'category'));
    }

    /**
     * Affiche la boutique d'un vendeur et ses produits
     */
    public function vendor(Vendor $vendor): View
    {
        if ($vendor->status !== 'approved') {
            abort(404);
        }

        $products = $vendor->products()
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->with(['category', 'vendor'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('client.vendors.show', compact('vendor', 'products'));
    }
}
