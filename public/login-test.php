<?php
// Start the session to get a CSRF token
session_start();

// Generate a new CSRF token if one doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    // Process login (just for testing)
    echo '<pre>';
    echo "Form submitted successfully!\n\n";
    echo "Email: " . htmlspecialchars($_POST['email']) . "\n";
    echo "Password: " . str_repeat('*', strlen($_POST['password'])) . "\n";
    echo '</pre>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Test</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Login Test Form</h1>
    <p>This is a simple test form to verify that form submissions and CSRF tokens are working correctly.</p>
    
    <form method="POST" action="">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-group">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <div style="margin-top: 20px;">
        <p><strong>Session Info:</strong></p>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
</body>
</html> 