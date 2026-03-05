<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class SimpleCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Électronique',
                'slug' => 'electronique',
                'status' => 'active',
            ],
            [
                'name' => 'Vêtements & Mode',
                'slug' => 'vetements-mode',
                'status' => 'active',
            ],
            [
                'name' => 'Alimentation & Boissons',
                'slug' => 'alimentation-boissons',
                'status' => 'active',
            ],
            [
                'name' => 'Maison & Jardin',
                'slug' => 'maison-jardin',
                'status' => 'active',
            ],
            [
                'name' => 'Beauté & Santé',
                'slug' => 'beaute-sante',
                'status' => 'active',
            ],
            [
                'name' => 'Sports & Loisirs',
                'slug' => 'sports-loisirs',
                'status' => 'active',
            ],
            [
                'name' => 'Livres & Médias',
                'slug' => 'livres-medias',
                'status' => 'active',
            ],
            [
                'name' => 'Automobile',
                'slug' => 'automobile',
                'status' => 'active',
            ],
            [
                'name' => 'Bébés & Enfants',
                'slug' => 'bebes-enfants',
                'status' => 'active',
            ],
            [
                'name' => 'Services',
                'slug' => 'services',
                'status' => 'active',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('✅ Catégories créées avec succès!');
        $this->command->info('📊 Total: ' . Category::where('status', 'active')->count() . ' catégories actives');
    }
}
