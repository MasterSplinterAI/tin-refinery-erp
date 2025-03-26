<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->index();
            $table->string('transaction_type', 50)->nullable();
            $table->string('xero_account_code', 10);
            $table->string('xero_account_name')->nullable();
            $table->timestamps();

            $table->unique(['module', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
}; 