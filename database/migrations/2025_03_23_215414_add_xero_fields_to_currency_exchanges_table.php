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
            // Add effective_rate if it doesn't exist
            if (!Schema::hasColumn('currency_exchanges', 'effective_rate')) {
                $table->decimal('effective_rate', 10, 4)->nullable()->after('exchange_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currency_exchanges', function (Blueprint $table) {
            // Drop effective_rate if it exists
            if (Schema::hasColumn('currency_exchanges', 'effective_rate')) {
                $table->dropColumn('effective_rate');
            }
        });
    }
};
