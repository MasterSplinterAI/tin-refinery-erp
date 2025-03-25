<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->string('status');
            $table->decimal('usd_amount', 15, 2);
            $table->decimal('cop_amount', 15, 2);
            $table->decimal('exchange_rate', 10, 4);
            $table->dateTime('exchange_date');
            $table->string('xero_invoice_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
}; 