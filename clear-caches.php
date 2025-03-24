<?php

echo "Clearing Laravel caches...\n";

// Run artisan commands to clear caches
$commands = [
    'php artisan config:clear',
    'php artisan cache:clear', 
    'php artisan view:clear',
    'php artisan route:clear'
];

foreach ($commands as $command) {
    echo "Running: $command\n";
    passthru($command, $returnCode);
    
    if ($returnCode !== 0) {
        echo "Error: Command '$command' failed with code $returnCode\n";
    }
}

echo "All caches cleared successfully!\n"; 