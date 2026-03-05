<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Migration pour corriger les chemins d'images existants
     * et les déplacer vers la nouvelle structure centralisée
     */
    public function up(): void
    {
        // 1. Corriger les chemins des produits
        $this->migrateProductImages();
        
        // 2. Corriger les chemins des vendeurs
        $this->migrateVendorImages();
    }

    /**
     * Inverser la migration
     */
    public function down(): void
    {
        // Logique de rollback si nécessaire
    }

    /**
     * Migrer les images des produits vers uploads/products/
     */
    private function migrateProductImages(): void
    {
        $products = DB::table('products')->whereNotNull('image')->get();
        
        foreach ($products as $product) {
            $oldPath = $product->image;
            
            // Ignorer si déjà au bon format
            if (str_starts_with($oldPath, 'uploads/products/')) {
                continue;
            }
            
            // Anciens formats à corriger
            if (str_starts_with($oldPath, 'products/')) {
                $newPath = 'uploads/' . $oldPath;
                
                // Déplacer le fichier si nécessaire
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->move($oldPath, $newPath);
                }
                
                // Mettre à jour la base
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['image' => $newPath]);
            }
        }
    }

    /**
     * Migrer les logos des vendeurs vers uploads/vendors/
     */
    private function migrateVendorImages(): void
    {
        $vendors = DB::table('vendors')->whereNotNull('logo')->get();
        
        foreach ($vendors as $vendor) {
            $oldPath = $vendor->logo;
            
            // Ignorer si déjà au bon format
            if (str_starts_with($oldPath, 'uploads/vendors/')) {
                continue;
            }
            
            // Anciens formats à corriger
            if (str_starts_with($oldPath, 'vendor-logos/')) {
                $newPath = 'uploads/vendors/' . basename($oldPath);
                
                // Déplacer le fichier si nécessaire
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->move($oldPath, $newPath);
                }
                
                // Mettre à jour la base
                DB::table('vendors')
                    ->where('id', $vendor->id)
                    ->update(['logo' => $newPath]);
            }
        }
    }
};
