<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get the connection
use Illuminate\Support\Facades\DB;
$connection = DB::connection();

// Get the table schema
$table = 'currency_exchanges';
$columns = $connection->getSchemaBuilder()->getColumnListing($table);

echo "Columns in $table table:\n";
foreach ($columns as $column) {
    echo "- $column\n";
}

// Check specifically for Xero fields
$xeroFields = [
    'xero_synced',
    'xero_sync_date',
    'xero_sync_error',
    'xero_reference',
    'xero_status',
    'bank_name',
];

echo "\nChecking for Xero fields:\n";
foreach ($xeroFields as $field) {
    if (in_array($field, $columns)) {
        echo "- $field: EXISTS\n";
    } else {
        echo "- $field: MISSING\n";
    }
} 