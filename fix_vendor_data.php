<?php

/**
 * Script de réparation des données vendeurs corrompues
 * Corrige les incohérences User-Vendor
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== RÉPARATION DONNÉES VENDORS ===\n\n";

// 1. Nettoyer les vendors sans user valide
echo "1. NETTOYAGE VENDORS SANS USER:\n";
$vendorsWithoutUser = \App\Models\Vendor::whereNull('user_id')->orWhereDoesntHave('user')->get();
echo "   Trouvé: " . $vendorsWithoutUser->count() . " vendors sans user\n";

foreach($vendorsWithoutUser as $vendor) {
    echo "   - Suppression du vendor: {$vendor->shop_name}\n";
    $vendor->forceDelete(); // Suppression définitive
}
echo "\n";

// 2. Trouver les users vendeur sans vendor
echo "2. USERS VENDEUR SANS VENDOR:\n";
$vendorUsersWithoutVendor = \App\Models\User::where('role', 'vendeur')->whereDoesntHave('vendor')->get();
echo "   Trouvé: " . $vendorUsersWithoutVendor->count() . " users vendeur sans vendor\n";

foreach($vendorUsersWithoutVendor as $user) {
    echo "   - Création vendor pour: {$user->email}\n";
    
    // Créer un vendor pour cet user
    \App\Models\Vendor::create([
        'user_id' => $user->id,
        'shop_name' => $user->name . ' Boutique',
        'description' => 'Boutique de ' . $user->name,
        'status' => 'pending', // En attente d'approbation
    ]);
}
echo "\n";

// 3. Vérifier les réparations
echo "3. VÉRIFICATION APRÈS RÉPARATION:\n";
echo "   Total vendors: " . \App\Models\Vendor::count() . "\n";
echo "   Users vendeur: " . \App\Models\User::where('role', 'vendeur')->count() . "\n";

// Vérifier que chaque user vendeur a bien un vendor
$vendorUsers = \App\Models\User::where('role', 'vendeur')->get();
$consistentCount = 0;
foreach($vendorUsers as $user) {
    if ($user->vendor) {
        $consistentCount++;
        echo "   ✅ {$user->email} -> {$user->vendor->shop_name}\n";
    } else {
        echo "   ❌ {$user->email} -> PAS DE VENDOR\n";
    }
}

echo "\n=== RÉPARATION TERMINÉE ===\n";
echo "Vendors cohérents: {$consistentCount}/" . $vendorUsers->count() . "\n";
