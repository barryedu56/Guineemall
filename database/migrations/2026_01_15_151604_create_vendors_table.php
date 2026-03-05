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
        Schema::create('vendors', function (Blueprint $table) {
        $table->id();

        // Lien avec l'utilisateur (vendeur)
        $table->foreignId('user_id')
              ->constrained()
              ->onDelete('cascade');

        // Infos boutique
        $table->string('shop_name');
        $table->text('description')->nullable();
        $table->string('logo')->nullable();

        // Statut géré par l'admin
        $table->enum('status', ['pending', 'approved', 'suspended'])
              ->default('pending');

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
