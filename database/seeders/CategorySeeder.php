<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
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
                'description' => 'Smartphones, ordinateurs, tablettes et accessoires électroniques',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vêtements & Mode',
                'slug' => 'vetements-mode',
                'description' => 'Vêtements pour hommes, femmes et enfants',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Alimentation & Boissons',
                'slug' => 'alimentation-boissons',
                'description' => 'Produits alimentaires et boissons diverses',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maison & Jardin',
                'slug' => 'maison-jardin',
                'description' => 'Meubles, décoration, articles de jardinage',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beauté & Santé',
                'slug' => 'beaute-sante',
                'description' => 'Cosmétiques, produits de beauté et articles de santé',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sports & Loisirs',
                'slug' => 'sports-loisirs',
                'description' => 'Équipements sportifs, jeux et loisirs',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Livres & Médias',
                'slug' => 'livres-medias',
                'description' => 'Livres, musique, films et jeux vidéo',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Automobile',
                'slug' => 'automobile',
                'description' => 'Pièces détachées, accessoires et entretien automobile',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bébés & Enfants',
                'slug' => 'bebes-enfants',
                'description' => 'Articles pour bébés et jeunes enfants',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Services',
                'slug' => 'services',
                'description' => 'Services divers et prestations',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('✅ Catégories créées avec succès!');
    }
}
