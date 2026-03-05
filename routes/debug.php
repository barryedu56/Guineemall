<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;

/*
|--------------------------------------------------------------------------
| Route de Debug pour les catégories
|--------------------------------------------------------------------------
*/

Route::get('/debug-categories', function () {
    try {
        // Vérifier si la table existe
        if (!\Schema::hasTable('categories')) {
            return response()->json([
                'error' => 'Table categories n\'existe pas',
                'solution' => 'Lancez: php artisan migrate'
            ], 500);
        }

        // Compter les catégories
        $totalCategories = Category::count();
        $activeCategories = Category::where('status', 'active')->count();

        // Si aucune catégorie, en créer
        if ($totalCategories === 0) {
            $defaultCategories = [
                ['name' => 'Électronique', 'slug' => 'electronique', 'status' => 'active'],
                ['name' => 'Vêtements', 'slug' => 'vetements', 'status' => 'active'],
                ['name' => 'Alimentation', 'slug' => 'alimentation', 'status' => 'active'],
                ['name' => 'Maison', 'slug' => 'maison', 'status' => 'active'],
                ['name' => 'Beauté', 'slug' => 'beaute', 'status' => 'active'],
                ['name' => 'Sports', 'slug' => 'sports', 'status' => 'active'],
            ];

            foreach ($defaultCategories as $cat) {
                Category::create($cat);
            }

            $totalCategories = Category::count();
            $activeCategories = Category::where('status', 'active')->count();
        }

        // Retourner les infos
        return response()->json([
            'success' => true,
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'categories' => Category::where('status', 'active')->get(),
            'message' => 'Catégories disponibles pour le formulaire vendeur'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Route pour créer une catégorie rapidement
Route::get('/create-category/{name}', function ($name) {
    try {
        $slug = \Str::slug($name);
        
        $category = Category::create([
            'name' => $name,
            'slug' => $slug,
            'description' => "Catégorie {$name}",
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'category' => $category,
            'message' => "Catégorie '{$name}' créée avec succès"
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});
