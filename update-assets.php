<?php

// This script automatically updates the asset paths in the blade files and htaccess
// It reads the manifest and extracts the latest file names

echo "Updating asset paths...\n";

// Path to manifest file
$manifestPath = __DIR__ . '/public/build/.vite/manifest.json';

if (!file_exists($manifestPath)) {
    echo "Error: Manifest file not found at {$manifestPath}\n";
    exit(1);
}

// Read and parse the manifest
$manifest = json_decode(file_get_contents($manifestPath), true);
if (!$manifest) {
    echo "Error: Failed to parse manifest JSON\n";
    exit(1);
}

// Get the latest JS and CSS paths
$jsPath = null;
$cssPath = null;

if (isset($manifest['resources/js/app.js']['file'])) {
    $jsPath = $manifest['resources/js/app.js']['file'];
    echo "Found JS path: {$jsPath}\n";
} else {
    echo "Warning: Could not find JS file in manifest\n";
}

if (isset($manifest['resources/css/app.css']['file'])) {
    $cssPath = $manifest['resources/css/app.css']['file'];
    echo "Found CSS path: {$cssPath}\n";
} else {
    echo "Warning: Could not find CSS file in manifest\n";
}

if (!$jsPath || !$cssPath) {
    echo "Error: Could not determine asset paths\n";
    exit(1);
}

// Files to update
$files = [
    __DIR__ . '/resources/views/app.blade.php',
    __DIR__ . '/resources/views/layouts/app.blade.php',
    __DIR__ . '/public/.htaccess'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Warning: File not found: {$file}\n";
        continue;
    }
    
    $content = file_get_contents($file);
    if ($content === false) {
        echo "Error: Could not read file: {$file}\n";
        continue;
    }
    
    // Update app.blade.php and layouts/app.blade.php
    if (strpos($file, 'blade.php') !== false) {
        // First, check if we're using dynamic variables
        if (strpos($content, '{{ $viteManifestCss }}') !== false) {
            echo "File {$file} is using dynamic variables, skipping...\n";
            continue;
        }
        
        // Replace the asset paths
        $pattern = '/asset\(\'build\/assets\/app-[^"\']+\.css\'\)/';
        $replacement = "asset('build/{$cssPath}')";
        $content = preg_replace($pattern, $replacement, $content);
        
        $pattern = '/asset\(\'build\/assets\/app-[^"\']+\.js\'\)/';
        $replacement = "asset('build/{$jsPath}')";
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Update .htaccess
    if (strpos($file, '.htaccess') !== false) {
        // Replace app.js path
        $pattern = '/RewriteRule \^\(\.\*\)\$ \/build\/assets\/app-[^\.]+\.js \[L\]/';
        $replacement = "RewriteRule ^(.*)$ /build/{$jsPath} [L]";
        $content = preg_replace($pattern, $replacement, $content);
        
        // Replace app.css path
        $pattern = '/RewriteRule \^\(\.\*\)\$ \/build\/assets\/app-[^\.]+\.css \[L\]/';
        $replacement = "RewriteRule ^(.*)$ /build/{$cssPath} [L]";
        $content = preg_replace($pattern, $replacement, $content);
        
        // Replace @vite/client path
        $pattern = '/RewriteRule \^\(\.\*\)\$ \/build\/assets\/app-[^\.]+\.js \[L\]/';
        $replacement = "RewriteRule ^(.*)$ /build/{$jsPath} [L]";
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Write the updated content back to the file
    if (file_put_contents($file, $content) === false) {
        echo "Error: Could not write to file: {$file}\n";
    } else {
        echo "Updated file: {$file}\n";
    }
}

echo "Asset paths updated successfully!\n"; 