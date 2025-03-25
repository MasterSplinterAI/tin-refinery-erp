<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('cost_basis_usd', 15, 2)->nullable();
            $table->decimal('cost_basis_cop', 15, 2)->nullable();
            $table->dateTime('last_purchase_date')->nullable();
            $table->decimal('last_purchase_price_usd', 15, 2)->nullable();
            $table->decimal('last_purchase_price_cop', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn([
                'cost_basis_usd',
                'cost_basis_cop',
                'last_purchase_date',
                'last_purchase_price_usd',
                'last_purchase_price_cop'
            ]);
        });
    }
}; 