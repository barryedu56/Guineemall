<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendeur = User::where('role', 'vendeur')->first();

        if (! $vendeur) {
            return;
        }

        Vendor::firstOrCreate(
            ['user_id' => $vendeur->id],
            [
                'shop_name'   => 'Boutique Test',
                'description' => 'Boutique de démonstration',
                'status'      => 'pending',
            ]
        );
    }
}
