<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            
            // Lien vers la commande principale du client
            $table->foreignId('order_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Le vendeur concerné
            $table->foreignId('vendor_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Montant total pour ce vendeur
            $table->decimal('total_amount', 10, 2);
            
            // Statut indépendant par vendeur
            $table->enum('status', [
                'pending',     // en attente (vendeur)
                'confirmed',   // confirmée (vendeur)
                'preparing',   // en préparation
                'ready',       // prête pour livraison
                'delivered',   // livrée
                'cancelled'    // annulée
            ])->default('pending');
            
            $table->timestamps();
            
            // Unicité : une seule sous-commande par vendeur par commande
            $table->unique(['order_id', 'vendor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_orders');
    }
};
