<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Notifications\ProductRejectedForVendor;
use App\Jobs\SendVendorNotificationJob;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Liste des produits (admin)
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'vendor']);

        // Filtrer par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrer par catégorie
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    /**
     * Détail d’un produit
     */
    public function show(Product $product)
    {
        $product->load(['category', 'vendor']);

        return view('admin.products.show', compact('product'));
    }

    /**
     * Activer un produit
     */
    public function activate(Product $product)
    {
        $product->update([
            'status' => 'active'
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produit activé avec succès');
    }

    /**
     * Désactiver un produit
     */
    public function deactivate(Product $product)
    {
        $product->update([
            'status' => 'inactive'
        ]);

        if ($product->vendor && $product->vendor->user) {
            SendVendorNotificationJob::dispatch(
                $product->vendor->user,
                new ProductRejectedForVendor($product)
            );
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produit désactivé');
    }
}
