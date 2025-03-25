<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Laravel Ngrok Debug Info</h1>";

// Basic PHP info
echo "<h2>PHP Version:</h2>";
echo "<pre>" . phpversion() . "</pre>";

// Check headers
echo "<h2>Request Headers:</h2>";
echo "<pre>";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0) {
        echo htmlspecialchars("$key: $value") . "\n";
    }
}
echo "</pre>";

// Check session
echo "<h2>Session Info:</h2>";
session_start();
echo "<pre>Session ID: " . session_id() . "</pre>";
echo "<pre>Session Status: " . session_status() . "</pre>";
echo "<pre>Session Save Path: " . session_save_path() . "</pre>";

// Check cookies
echo "<h2>Cookies:</h2>";
echo "<pre>";
foreach ($_COOKIE as $name => $value) {
    echo htmlspecialchars("$name: $value") . "\n";
}
echo "</pre>";

// Check Laravel environment
echo "<h2>Environment Variables:</h2>";
echo "<pre>";
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $env_content = file_get_contents($env_file);
    $lines = explode("\n", $env_content);
    foreach ($lines as $line) {
        if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Mask sensitive values
        if (preg_match('/^(APP_KEY|.*SECRET|.*PASSWORD)=(.*)$/i', $line, $matches)) {
            echo htmlspecialchars($matches[1] . "=[MASKED]") . "\n";
        } else if (strpos($line, '=') !== false) {
            echo htmlspecialchars($line) . "\n";
        }
    }
}
echo "</pre>";

// Test a simple database connection if possible
echo "<h2>Database Connection Test:</h2>";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    
    try {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_DATABASE') ?: 'tin_refinery';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$database";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<pre style='color:green;'>Database connection successful!</pre>";
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        echo "<pre>Total users in database: $count</pre>";
    } catch (PDOException $e) {
        echo "<pre style='color:red;'>Database connection failed: " . htmlspecialchars($e->getMessage()) . "</pre>";
    }
} else {
    echo "<pre style='color:orange;'>Vendor autoload not found. Skipping database test.</pre>";
}

// Show server information
echo "<h2>Server Info:</h2>";
echo "<pre>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
echo "SERVER_ADDR: " . ($_SERVER['SERVER_ADDR'] ?? 'Not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'On' : 'Off') . "\n";
echo "</pre>";

// Show debug for Laravel session config
echo "<h2>Laravel Session Config Test:</h2>";
if (file_exists(__DIR__ . '/../config/session.php')) {
    echo "<pre>";
    echo "Session config file exists.\n";
    $session_config = include __DIR__ . '/../config/session.php';
    echo "Driver: " . ($session_config['driver'] ?? 'Not set') . "\n";
    echo "Lifetime: " . ($session_config['lifetime'] ?? 'Not set') . "\n";
    echo "Secure: " . ($session_config['secure'] ? 'Yes' : 'No') . "\n";
    echo "Same Site: " . ($session_config['same_site'] ?? 'Not set') . "\n";
    echo "Domain: " . ($session_config['domain'] ?? 'Not set') . "\n";
    echo "</pre>";
} else {
    echo "<pre style='color:red;'>Session config file not found!</pre>";
} 