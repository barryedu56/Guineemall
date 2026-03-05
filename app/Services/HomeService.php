<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HomeService
{
    /**
     * Récupère les 4 produits les plus populaires selon la logique de priorité
     * 
     * @return Collection
     */
    public function getPopularProducts(): Collection
    {
        return Cache::remember('popular_products', 600, function () {
            // Priorité 1: Produits les plus vendus (basé sur les commandes)
            $popularBySales = $this->getProductsBySales();
            
            if ($popularBySales->count() >= 4) {
                return $popularBySales->take(4);
            }

            // Priorité 2: Produits les plus consultés (si disponible)
            $popularByViews = $this->getProductsByViews();
            
            if ($popularByViews->count() >= 4) {
                return $popularByViews->take(4);
            }

            // Fallback: Combiner ventes + vues + derniers produits
            return $this->getFallbackPopularProducts($popularBySales, $popularByViews);
        });
    }

    /**
     * Récupère les produits les plus vendus
     * 
     * @return Collection
     */
    private function getProductsBySales(): Collection
    {
        return Product::where('status', 'active')
            ->with(['category', 'vendor'])
            ->withCount(['orderItems as total_sales'])
            ->having('total_sales', '>', 0)
            ->orderBy('total_sales', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Récupère les produits les plus consultés
     * Vérifie si le champ 'views' existe dans la table
     * 
     * @return Collection
     */
    private function getProductsByViews(): Collection
    {
        // Vérifier si la colonne 'views' existe
        if (!\Schema::hasColumn('products', 'views')) {
            return collect();
        }

        return Product::where('status', 'active')
            ->with(['category', 'vendor'])
            ->where('views', '>', 0)
            ->orderBy('views', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Fallback intelligent pour obtenir 4 produits populaires
     * 
     * @param Collection $popularBySales
     * @param Collection $popularByViews
     * @return Collection
     */
    private function getFallbackPopularProducts(Collection $popularBySales, Collection $popularByViews): Collection
    {
        $products = collect();
        
        // Ajouter les produits avec ventes
        $products = $products->merge($popularBySales);
        
        // Ajouter les produits avec vues (non dupliqués)
        $salesIds = $popularBySales->pluck('id')->toArray();
        $viewsWithoutSales = $popularByViews->reject(function ($product) use ($salesIds) {
            return in_array($product->id, $salesIds);
        });
        $products = $products->merge($viewsWithoutSales);
        
        // Si on a moins de 4 produits, compléter avec les derniers produits actifs
        if ($products->count() < 4) {
            $existingIds = $products->pluck('id')->toArray();
            $recentProducts = Product::where('status', 'active')
                ->with(['category', 'vendor'])
                ->whereNotIn('id', $existingIds)
                ->orderBy('created_at', 'desc')
                ->take(4 - $products->count())
                ->get();
            
            $products = $products->merge($recentProducts);
        }
        
        return $products->take(4);
    }
}
