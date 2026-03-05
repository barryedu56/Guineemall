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
        Schema::create('orders', function (Blueprint $table) {
        $table->id();

        // Client qui passe la commande
        $table->foreignId('user_id')
              ->constrained()
              ->cascadeOnDelete();

        // Montant total de la commande
        $table->decimal('total_amount', 10, 2);

        // Statut de la commande
        $table->enum('status', [
            'pending',     // en attente
            'processing',  // en cours
            'delivered',   // livrée
            'cancelled'    // annulée
        ])->default('pending');

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
