<?php
// Very simple test file to see if ngrok can serve static PHP files
header('Content-Type: text/plain');
echo "Hello from test.php!\n\n";
echo "Server time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP version: " . phpversion() . "\n";
echo "\nRequest Information:\n";
echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'Not available') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not available') . "\n";
echo "Client IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Not available') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not available') . "\n";
echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Not available') . "\n";

echo "\nServer Information:\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not available') . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'Yes' : 'No') . "\n";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Not available') . "\n";
echo "Server Port: " . ($_SERVER['SERVER_PORT'] ?? 'Not available') . "\n";

echo "\nHTTP Headers:\n";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
} 