<?php
/**
 * Automated Setup Script
 * Run this after uploading: http://cpdemo.blueinctech.com/setup.php
 * This will configure your .env file and set permissions
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Goodwill Staffing - Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🚀 Goodwill Staffing - Setup Wizard</h1>
    
<?php

$backendPath = __DIR__ . '/backend';
$envPath = $backendPath . '/.env';
$storagePath = $backendPath . '/storage';
$cachePath = $backendPath . '/bootstrap/cache';

$steps = [];
$allGood = true;

// Step 1: Check backend folder
echo "<h2>Step 1: Checking File Structure</h2>";
if (is_dir($backendPath)) {
    echo "<div class='success'>✓ Backend folder exists</div>";
    $steps[] = true;
} else {
    echo "<div class='error'>✗ Backend folder not found at: $backendPath</div>";
    $steps[] = false;
    $allGood = false;
}

// Step 2: Check .env file
echo "<h2>Step 2: Checking Configuration</h2>";
if (file_exists($envPath)) {
    echo "<div class='success'>✓ .env file exists</div>";
    
    $envContent = file_get_contents($envPath);
    $needsKey = strpos($envContent, 'APP_KEY=base64:CHANGE_THIS_KEY_AFTER_UPLOAD') !== false;
    
    if ($needsKey) {
        echo "<div class='warning'>⚠ APP_KEY needs to be generated</div>";
        $steps[] = false;
        $allGood = false;
    } else {
        echo "<div class='success'>✓ APP_KEY is set</div>";
        $steps[] = true;
    }
} else {
    echo "<div class='error'>✗ .env file not found. Creating from template...</div>";
    // Create .env from template if possible
    $steps[] = false;
    $allGood = false;
}

// Step 3: Generate APP_KEY if needed
if (isset($_GET['generate_key']) && file_exists($envPath)) {
    echo "<h2>Step 3: Generating APP_KEY</h2>";
    $newKey = 'base64:' . base64_encode(random_bytes(32));
    $envContent = file_get_contents($envPath);
    $envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $newKey, $envContent);
    
    if (file_put_contents($envPath, $envContent)) {
        echo "<div class='success'>✓ APP_KEY generated and saved!</div>";
        echo "<div class='info'>Your APP_KEY has been set. You can now proceed.</div>";
    } else {
        echo "<div class='error'>✗ Could not write to .env file. Please check permissions.</div>";
    }
}

// Step 4: Check storage permissions
echo "<h2>Step 4: Checking Permissions</h2>";
if (is_dir($storagePath)) {
    $writable = is_writable($storagePath);
    if ($writable) {
        echo "<div class='success'>✓ Storage folder is writable</div>";
        $steps[] = true;
    } else {
        echo "<div class='warning'>⚠ Storage folder is not writable</div>";
        echo "<div class='info'>You need to set permissions: chmod -R 775 backend/storage</div>";
        $steps[] = false;
        $allGood = false;
    }
} else {
    echo "<div class='error'>✗ Storage folder not found</div>";
    $steps[] = false;
    $allGood = false;
}

if (is_dir($cachePath)) {
    $writable = is_writable($cachePath);
    if ($writable) {
        echo "<div class='success'>✓ Cache folder is writable</div>";
        $steps[] = true;
    } else {
        echo "<div class='warning'>⚠ Cache folder is not writable</div>";
        echo "<div class='info'>You need to set permissions: chmod -R 775 backend/bootstrap/cache</div>";
        $steps[] = false;
        $allGood = false;
    }
}

// Step 5: Check database configuration
echo "<h2>Step 5: Database Configuration</h2>";
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    $dbConfigured = !empty($env['DB_DATABASE']) && 
                     $env['DB_DATABASE'] !== 'your_database_name' &&
                     !empty($env['DB_USERNAME']) &&
                     $env['DB_USERNAME'] !== 'your_database_user';
    
    if ($dbConfigured) {
        echo "<div class='success'>✓ Database is configured</div>";
        $steps[] = true;
    } else {
        echo "<div class='warning'>⚠ Database needs to be configured</div>";
        echo "<div class='info'>Edit backend/.env and set:<br>";
        echo "DB_DATABASE=your_actual_database_name<br>";
        echo "DB_USERNAME=your_actual_database_user<br>";
        echo "DB_PASSWORD=your_actual_database_password</div>";
        $steps[] = false;
        $allGood = false;
    }
}

// Summary
echo "<h2>📋 Setup Summary</h2>";
$completed = count(array_filter($steps));
$total = count($steps);
echo "<div class='info'>Completed: $completed / $total steps</div>";

if ($allGood) {
    echo "<div class='success'><h3>✅ All checks passed! Your application should be ready.</h3></div>";
    echo "<div class='info'>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Make sure your database is created and migrations are run (if you have terminal access)</li>";
    echo "<li>Test the API: <a href='api/test.php' target='_blank'>http://cpdemo.blueinctech.com/api/test.php</a></li>";
    echo "<li>Try logging in to your application</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='warning'><h3>⚠️ Some configuration is needed</h3></div>";
    
    if (file_exists($envPath) && strpos(file_get_contents($envPath), 'APP_KEY=base64:CHANGE_THIS_KEY_AFTER_UPLOAD') !== false) {
        echo "<div class='info'>";
        echo "<p><strong>Quick Fix: Generate APP_KEY</strong></p>";
        echo "<a href='?generate_key=1'><button>Generate APP_KEY Now</button></a>";
        echo "</div>";
    }
    
    echo "<div class='info'>";
    echo "<p><strong>Manual Steps:</strong></p>";
    echo "<ol>";
    if (!file_exists($envPath)) {
        echo "<li>Create backend/.env file (copy from backend/.env.example if available)</li>";
    }
    if (file_exists($envPath) && strpos(file_get_contents($envPath), 'APP_KEY=base64:CHANGE_THIS_KEY_AFTER_UPLOAD') !== false) {
        echo "<li>Generate APP_KEY: Visit <a href='backend/generate-key.php' target='_blank'>backend/generate-key.php</a> and copy the key to .env</li>";
    }
    echo "<li>Configure database in backend/.env</li>";
    echo "<li>Set permissions: chmod -R 775 backend/storage backend/bootstrap/cache</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<div class='info'>";
echo "<p><strong>Need help?</strong></p>";
echo "<ul>";
echo "<li>Check diagnostic: <a href='api/test.php' target='_blank'>api/test.php</a></li>";
echo "<li>View logs: backend/storage/logs/laravel.log</li>";
echo "</ul>";
echo "</div>";

?>
</body>
</html>




