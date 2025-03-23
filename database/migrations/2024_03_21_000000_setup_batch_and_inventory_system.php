<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only create inventory_items table if it doesn't exist
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type');
                $table->text('description')->nullable();
                $table->decimal('quantity', 10, 2)->default(0);
                $table->string('unit');
                $table->decimal('sn_content', 5, 2)->nullable();
                $table->decimal('unit_price', 10, 2)->nullable();
                $table->string('currency', 3);
                $table->string('location')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Create batches table
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('batchNumber');
            $table->dateTime('date');
            $table->enum('status', ['in_progress', 'completed', 'cancelled']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Create processes table
        Schema::create('processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batchId')->constrained('batches')->onDelete('cascade');
            $table->integer('processNumber');
            $table->string('processingType')->default('kaldo_furnace');
            $table->decimal('inputTinKilos', 10, 2)->nullable();
            $table->decimal('inputTinSnContent', 5, 2)->nullable();
            $table->decimal('outputTinKilos', 10, 2)->nullable();
            $table->decimal('outputTinSnContent', 5, 2)->nullable();
            $table->decimal('inputSlagKilos', 10, 2)->nullable();
            $table->decimal('inputSlagSnContent', 5, 2)->nullable();
            $table->decimal('outputSlagKilos', 10, 2)->nullable();
            $table->decimal('outputSlagSnContent', 5, 2)->nullable();
            $table->unsignedBigInteger('inputTinInventoryItemId')->nullable();
            $table->unsignedBigInteger('outputTinInventoryItemId')->nullable();
            $table->unsignedBigInteger('inputSlagInventoryItemId')->nullable();
            $table->unsignedBigInteger('outputSlagInventoryItemId')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('inputTinInventoryItemId')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('set null');

            $table->foreign('outputTinInventoryItemId')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('set null');

            $table->foreign('inputSlagInventoryItemId')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('set null');

            $table->foreign('outputSlagInventoryItemId')
                ->references('id')
                ->on('inventory_items')
                ->onDelete('set null');
        });

        // Create inventory transactions table
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->enum('type', ['consumption', 'production', 'reversal', 'adjustment']);
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('currency', 3);
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('processes');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('inventory_items');
    }
}; 