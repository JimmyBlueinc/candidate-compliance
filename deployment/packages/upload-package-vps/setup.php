<?php
/**
 * Goodwill Staffing - Web-Based Setup Script
 * Run this via browser: http://cpdemo.blueinctech.com/setup.php
 * DELETE THIS FILE after setup is complete!
 */

// Security: Only allow if .env doesn't exist or in development
$envExists = file_exists(__DIR__ . '/backend/.env');
if ($envExists && !isset($_GET['force'])) {
    die('Setup already completed. Add ?force=1 to URL to run again. DELETE THIS FILE after setup!');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Goodwill Staffing - VPS Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .step { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Goodwill Staffing - VPS Setup</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Process form submission
        $appUrl = $_POST['app_url'] ?? '';
        $frontendUrl = $_POST['frontend_url'] ?? '';
        $dbHost = $_POST['db_host'] ?? 'localhost';
        $dbName = $_POST['db_name'] ?? '';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPass = $_POST['db_pass'] ?? '';
        
        // Generate APP_KEY
        $appKey = 'base64:' . base64_encode(random_bytes(32));
        
        // Create .env content
        $envContent = <<<ENV
APP_NAME="Goodwill Staffing Compliance Tracker"
APP_ENV=production
APP_DEBUG=false
APP_URL={$appUrl}
APP_KEY={$appKey}

DB_CONNECTION=mysql
DB_HOST={$dbHost}
DB_PORT=3306
DB_DATABASE={$dbName}
DB_USERNAME={$dbUser}
DB_PASSWORD={$dbPass}

FRONTEND_URL={$frontendUrl}

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=your_email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Goodwill Staffing"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=cpdemo.blueinctech.com
ENV;
        
        // Write .env file
        $envPath = __DIR__ . '/backend/.env';
        if (file_put_contents($envPath, $envContent)) {
            echo '<div class="step success"><strong>✓ .env file created successfully!</strong></div>';
            
            // Set permissions
            chmod($envPath, 0600);
            if (is_dir(__DIR__ . '/backend/storage')) {
                chmod(__DIR__ . '/backend/storage', 0775);
            }
            if (is_dir(__DIR__ . '/backend/bootstrap/cache')) {
                chmod(__DIR__ . '/backend/bootstrap/cache', 0775);
            }
            
            echo '<div class="step success"><strong>✓ Permissions set!</strong></div>';
            echo '<div class="step"><strong>Next Steps:</strong><ol>';
            echo '<li>Delete this setup.php file for security!</li>';
            echo '<li>Rebuild frontend with: <code>VITE_API_BASE_URL=http://cpdemo.blueinctech.com/api</code></li>';
            echo '<li>If you have terminal/SSH access, run: <code>cd backend && php artisan migrate --force</code></li>';
            echo '<li>Test your website: <a href="' . $frontendUrl . '">' . $frontendUrl . '</a></li>';
            echo '<li>Test API: <a href="' . $appUrl . '/health">' . $appUrl . '/health</a></li>';
            echo '</ol></div>';
        } else {
            echo '<div class="step error"><strong>✗ Error creating .env file. Please check file permissions.</strong></div>';
        }
    } else {
        // Show form
        $currentDomain = $_SERVER['HTTP_HOST'] ?? 'cpdemo.blueinctech.com';
        $protocol = 'http';
        $suggestedUrl = $protocol . '://' . $currentDomain;
    ?>
    
    <div class="step warning">
        <strong>⚠ Security Warning:</strong> Delete this file (setup.php) immediately after completing setup!
    </div>
    
    <form method="POST">
        <div class="step">
            <h3>Domain Configuration</h3>
            <label>Frontend URL (Your Website):</label>
            <input type="text" name="frontend_url" value="<?php echo $suggestedUrl; ?>" required>
            
            <label>Backend API URL:</label>
            <input type="text" name="app_url" value="<?php echo $suggestedUrl; ?>/api" required>
        </div>
        
        <div class="step">
            <h3>Database Configuration</h3>
            <label>Database Host:</label>
            <input type="text" name="db_host" value="localhost" required>
            
            <label>Database Name:</label>
            <input type="text" name="db_name" required placeholder="goodwill_db">
            
            <label>Database Username:</label>
            <input type="text" name="db_user" required>
            
            <label>Database Password:</label>
            <input type="password" name="db_pass" required>
        </div>
        
        <button type="submit">Create .env File & Set Permissions</button>
    </form>
    
    <?php } ?>
    
    <div class="step">
        <h3>Manual Setup (If form doesn't work)</h3>
        <p>If you have SSH/terminal access, you can run:</p>
        <pre>bash setup.sh</pre>
        <p>Or manually:</p>
        <ol>
            <li>Copy <code>backend/.env.example</code> to <code>backend/.env</code></li>
            <li>Edit <code>backend/.env</code> with your values:
                <ul>
                    <li><code>APP_URL=http://cpdemo.blueinctech.com/api</code></li>
                    <li><code>FRONTEND_URL=http://cpdemo.blueinctech.com</code></li>
                    <li>Database credentials</li>
                </ul>
            </li>
            <li>Run: <code>chmod -R 775 backend/storage backend/bootstrap/cache</code></li>
            <li>Run: <code>cd backend && php artisan key:generate</code></li>
        </ol>
    </div>
</body>
</html>
