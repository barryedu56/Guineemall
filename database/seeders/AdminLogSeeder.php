<?php

namespace Database\Seeders;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un admin si aucun n'existe
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin Test',
                'email' => 'admin@gm.test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        // Créer quelques logs d'exemple
        $sampleLogs = [
            'Création vendeur #1 - ["name", "email", "phone"]',
            'Approbation vendeur #1',
            'Suspension vendeur #2',
            'Création catégorie #5 - ["name", "description"]',
            'Suppression catégorie #3',
            'Mise à jour produit #10 - ["name", "price", "status"]',
            'Blocage client #15',
            'Activation client #18',
            'Consultation commandes',
            'Affichage commande #25',
        ];

        foreach ($sampleLogs as $action) {
            AdminLog::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'created_at' => now()->subMinutes(rand(1, 1440)), // Aléatoire dans les dernières 24h
            ]);
        }

        $this->command->info('Admin logs créés avec succès!');
    }
}
