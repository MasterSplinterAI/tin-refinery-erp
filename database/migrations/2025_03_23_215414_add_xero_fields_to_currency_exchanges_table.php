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
        Schema::table('currency_exchanges', function (Blueprint $table) {
            // Add effective_rate field to store the calculated effective rate
            $table->decimal('effective_rate', 10, 4)->nullable()->after('exchange_rate');
            
            // Add Xero-specific fields
            $table->string('xero_bill_id')->nullable()->after('xero_invoice_id');
            $table->string('xero_status')->default('pending')->after('xero_bill_id');
            $table->decimal('xero_currency_rate', 10, 4)->nullable()->after('xero_status');
            
            // Rename xero_invoice_id to avoid confusion since we're using bills
            $table->renameColumn('xero_invoice_id', 'xero_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            // Remove the added fields
            $table->dropColumn('effective_rate');
            $table->dropColumn('xero_bill_id');
            $table->dropColumn('xero_status');
            $table->dropColumn('xero_currency_rate');
            
            // Restore original column name
            $table->renameColumn('xero_reference', 'xero_invoice_id');
        });
    }
};
