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
        Schema::table('carts', function (Blueprint $table) {
            // Ajouter le champ session_id pour les paniers invités
            $table->string('session_id')->nullable()->after('user_id');
            
            // Ajouter un index pour optimiser les recherches
            $table->index('session_id');
            
            // Modifier la contrainte d'unicité pour permettre les paniers invités
            $table->dropUnique(['user_id', 'product_id']);
            $table->unique(['user_id', 'product_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropUnique(['user_id', 'product_id', 'session_id']);
            $table->dropColumn('session_id');
            
            // Recréer l'ancienne contrainte d'unicité
            $table->unique(['user_id', 'product_id']);
        });
    }
};
