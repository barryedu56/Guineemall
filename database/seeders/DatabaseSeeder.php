<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ADMIN
        User::firstOrCreate(
            ['email' => 'admin@guineemall.com'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // CLIENT
        User::firstOrCreate(
            ['email' => 'client@guineemall.com'],
            [
                'name' => 'Client Test',
                'password' => Hash::make('client123'),
                'role' => 'client',
            ]
        );

        // VENDEUR
        User::firstOrCreate(
            ['email' => 'vendeur@guineemall.com'],
            [
                'name' => 'Vendeur Test',
                'password' => Hash::make('vendeur123'),
                'role' => 'vendeur',
            ]
        );

        // 👉 SEEDERS
        $this->call([
            CategorySeeder::class,    // Catégories par défaut
            VendorSeeder::class,      // Vendeurs par défaut
            AdminLogSeeder::class,    // Logs d'exemple
        ]);
    }
}
