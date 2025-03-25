<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_exchanges', function (Blueprint $table) {
            $table->id();
            $table->date('exchange_date');
            $table->decimal('usd_amount', 15, 2);
            $table->decimal('cop_amount', 15, 2);
            $table->decimal('exchange_rate', 10, 4);
            $table->decimal('bank_fee_usd', 15, 2)->default(0);
            $table->decimal('bank_fee_cop', 15, 2)->default(0);
            $table->string('bank_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('xero_invoice_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_exchanges');
    }
}; 