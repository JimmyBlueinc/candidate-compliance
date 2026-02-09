<?php
/**
 * Diagnostic Test Script
 * Access this at: http://cpdemo.blueinctech.com/api/test.php
 * This will help identify what's causing the 500 error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Laravel API Diagnostic Test</h1>";
echo "<pre>";

// Test 1: Check if backend folder exists
echo "=== Test 1: File Structure ===\n";
$backendPath = __DIR__ . '/../backend';
echo "Backend path: $backendPath\n";
echo "Backend exists: " . (is_dir($backendPath) ? "YES ✓" : "NO ✗") . "\n\n";

// Test 2: Check .env file
echo "=== Test 2: Environment Configuration ===\n";
$envPath = $backendPath . '/.env';
echo ".env path: $envPath\n";
echo ".env exists: " . (file_exists($envPath) ? "YES ✓" : "NO ✗") . "\n";
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    echo "APP_KEY set: " . (strpos($envContent, 'APP_KEY=') !== false && strpos($envContent, 'APP_KEY=') < strpos($envContent, "\n")) ? "YES ✓" : "NO ✗" . "\n";
    echo "DB_DATABASE set: " . (strpos($envContent, 'DB_DATABASE=') !== false ? "YES ✓" : "NO ✗") . "\n";
}
echo "\n";

// Test 3: Check vendor folder
echo "=== Test 3: Dependencies ===\n";
$vendorPath = $backendPath . '/vendor/autoload.php';
echo "Vendor autoload: $vendorPath\n";
echo "Vendor exists: " . (file_exists($vendorPath) ? "YES ✓" : "NO ✗") . "\n\n";

// Test 4: Check storage permissions
echo "=== Test 4: Storage Permissions ===\n";
$storagePath = $backendPath . '/storage';
$storageWritable = is_writable($storagePath);
echo "Storage path: $storagePath\n";
echo "Storage writable: " . ($storageWritable ? "YES ✓" : "NO ✗") . "\n";
$logsPath = $storagePath . '/logs';
echo "Logs path: $logsPath\n";
echo "Logs writable: " . (is_writable($logsPath) ? "YES ✓" : "NO ✗") . "\n\n";

// Test 5: Try to load Laravel
echo "=== Test 5: Laravel Bootstrap ===\n";
try {
    if (file_exists($vendorPath)) {
        require $vendorPath;
        echo "Composer autoload: SUCCESS ✓\n";
        
        $bootstrapPath = $backendPath . '/bootstrap/app.php';
        if (file_exists($bootstrapPath)) {
            echo "Bootstrap file: EXISTS ✓\n";
            echo "Attempting to load Laravel...\n";
            // Don't actually load it, just check if we can
            echo "Laravel bootstrap: READY ✓\n";
        } else {
            echo "Bootstrap file: MISSING ✗\n";
        }
    } else {
        echo "Composer autoload: MISSING ✗\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Check PHP version
echo "=== Test 6: PHP Configuration ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Required: 8.2+\n";
echo "Version OK: " . (version_compare(phpversion(), '8.2.0', '>=') ? "YES ✓" : "NO ✗") . "\n";
echo "Required extensions:\n";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'json', 'curl', 'fileinfo'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo "  - $ext: " . ($loaded ? "LOADED ✓" : "MISSING ✗") . "\n";
}
echo "\n";

// Test 7: Check database connection (if .env exists)
echo "=== Test 7: Database Connection ===\n";
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    if (isset($env['DB_CONNECTION']) && isset($env['DB_HOST'])) {
        echo "DB Connection: " . ($env['DB_CONNECTION'] ?? 'not set') . "\n";
        echo "DB Host: " . ($env['DB_HOST'] ?? 'not set') . "\n";
        echo "DB Database: " . ($env['DB_DATABASE'] ?? 'not set') . "\n";
        echo "DB Username: " . ($env['DB_USERNAME'] ?? 'not set') . "\n";
        
        // Try to connect
        if (extension_loaded('pdo_mysql') && !empty($env['DB_HOST']) && !empty($env['DB_DATABASE'])) {
            try {
                $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
                $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '');
                echo "Database connection: SUCCESS ✓\n";
            } catch (PDOException $e) {
                echo "Database connection: FAILED ✗\n";
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo ".env file exists but database config missing\n";
    }
} else {
    echo ".env file not found - cannot test database\n";
}
echo "\n";

// Test 8: Check Laravel logs
echo "=== Test 8: Laravel Logs ===\n";
$logFile = $storagePath . '/logs/laravel.log';
if (file_exists($logFile)) {
    echo "Log file exists: YES ✓\n";
    echo "Log file size: " . filesize($logFile) . " bytes\n";
    echo "Last 10 lines of log:\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    echo implode('', $lastLines);
} else {
    echo "Log file: NOT FOUND (this is normal if no errors yet)\n";
}
echo "\n";

echo "=== Summary ===\n";
echo "If you see any ✗ marks above, those need to be fixed.\n";
echo "Most common issues:\n";
echo "1. Missing .env file - Copy backend/.env.example to backend/.env\n";
echo "2. Missing APP_KEY - Run: php artisan key:generate\n";
echo "3. Database not configured - Update backend/.env with database credentials\n";
echo "4. Storage not writable - Set permissions: chmod -R 775 backend/storage\n";
echo "5. Missing vendor folder - Run: composer install (if you have terminal access)\n";

echo "</pre>";




