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
            // Add Xero sync status fields if they don't exist
            if (!Schema::hasColumn('currency_exchanges', 'xero_synced')) {
                $table->boolean('xero_synced')->default(false)->after('notes');
            }
            
            if (!Schema::hasColumn('currency_exchanges', 'xero_sync_date')) {
                $table->dateTime('xero_sync_date')->nullable()->after('xero_synced');
            }
            
            if (!Schema::hasColumn('currency_exchanges', 'xero_sync_error')) {
                $table->string('xero_sync_error')->nullable()->after('xero_sync_date');
            }
            
            if (!Schema::hasColumn('currency_exchanges', 'xero_reference')) {
                $table->string('xero_reference')->nullable()->after('xero_sync_error');
            }
            
            if (!Schema::hasColumn('currency_exchanges', 'xero_status')) {
                $table->string('xero_status')->nullable()->after('xero_reference');
            }
            
            // Add bank name field if it doesn't exist
            if (!Schema::hasColumn('currency_exchanges', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('bank_fee_cop');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            // Drop columns only if they exist
            $columns = [
                'xero_synced',
                'xero_sync_date',
                'xero_sync_error',
                'xero_reference',
                'xero_status',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('currency_exchanges', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Only drop bank_name if it exists and we added it
            if (Schema::hasColumn('currency_exchanges', 'bank_name')) {
                $table->dropColumn('bank_name');
            }
        });
    }
};
