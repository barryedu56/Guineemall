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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            
            // Client propriétaire du panier
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Produit dans le panier
            $table->foreignId('product_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Quantité du produit
            $table->integer('quantity')->default(1);
            
            $table->timestamps();
            
            // Unicité : un produit ne peut apparaître qu'une fois par panier client
            $table->unique(['user_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
