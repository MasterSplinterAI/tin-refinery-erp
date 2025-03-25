<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('rate', 10, 4);
            $table->date('date');
            $table->string('source')->default('manual'); // manual, api, etc.
            $table->string('api_provider')->nullable(); // name of the API if source is api
            $table->json('metadata')->nullable(); // additional data from API
            $table->timestamps();

            // Ensure we don't have duplicate rates for the same date
            $table->unique('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
}; 