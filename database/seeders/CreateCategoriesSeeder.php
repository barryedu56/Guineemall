<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CreateCategoriesSeeder extends Seeder
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
            ],
            [
                'name' => 'Vêtements & Mode',
                'slug' => 'vetements-mode',
                'description' => 'Vêtements pour hommes, femmes et enfants',
                'status' => 'active',
            ],
            [
                'name' => 'Alimentation & Boissons',
                'slug' => 'alimentation-boissons',
                'description' => 'Produits alimentaires et boissons diverses',
                'status' => 'active',
            ],
            [
                'name' => 'Maison & Jardin',
                'slug' => 'maison-jardin',
                'description' => 'Meubles, décoration, articles de jardinage',
                'status' => 'active',
            ],
            [
                'name' => 'Beauté & Santé',
                'slug' => 'beaute-sante',
                'description' => 'Cosmétiques, produits de beauté et articles de santé',
                'status' => 'active',
            ],
            [
                'name' => 'Sports & Loisirs',
                'slug' => 'sports-loisirs',
                'description' => 'Équipements sportifs, jeux et loisirs',
                'status' => 'active',
            ],
            [
                'name' => 'Livres & Médias',
                'slug' => 'livres-medias',
                'description' => 'Livres, musique, films et jeux vidéo',
                'status' => 'active',
            ],
            [
                'name' => 'Automobile',
                'slug' => 'automobile',
                'description' => 'Pièces détachées, accessoires et entretien automobile',
                'status' => 'active',
            ],
            [
                'name' => 'Bébés & Enfants',
                'slug' => 'bebes-enfants',
                'description' => 'Articles pour bébés et jeunes enfants',
                'status' => 'active',
            ],
            [
                'name' => 'Services',
                'slug' => 'services',
                'description' => 'Services divers et prestations',
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
