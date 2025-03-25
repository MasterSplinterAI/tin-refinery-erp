<?php
// Disable any output buffering
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers to prevent caching
header('Content-Type: text/plain');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Output directly
echo "DIRECT OUTPUT TEST\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'Not available') . "\n";
echo "Hello World!\n";

// Force output flush
flush();
exit(); 