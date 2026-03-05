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
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status', 'orders_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('status', 'products_status_index');
            $table->index('vendor_id', 'products_vendor_id_index');
        });

        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->index('status', 'vendor_orders_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_index');
            $table->dropIndex('products_vendor_id_index');
        });

        Schema::table('vendor_orders', function (Blueprint $table) {
            $table->dropIndex('vendor_orders_status_index');
        });
    }
};
