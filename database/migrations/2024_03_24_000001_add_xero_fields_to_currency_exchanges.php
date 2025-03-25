<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->string('xero_bill_id')->nullable()->after('xero_invoice_id');
            $table->decimal('xero_currency_rate', 10, 4)->nullable()->after('xero_bill_id');
            $table->string('xero_status')->default('pending')->after('xero_currency_rate');
            $table->string('bank_statement_file')->nullable()->after('xero_status');
        });
    }

    public function down(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            $table->dropColumn([
                'xero_bill_id',
                'xero_currency_rate',
                'xero_status',
                'bank_statement_file'
            ]);
        });
    }
}; 