<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['cassiterite', 'ingot', 'finished_tin', 'slag']);
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->enum('unit', ['kg', 'ton', 'pieces']);
            $table->decimal('sn_content', 5, 2);
            $table->string('location');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
}; 