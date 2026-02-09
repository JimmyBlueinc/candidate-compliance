# Goodwill Staffing - Backend Deployment Script (PowerShell)
# This script prepares the Laravel backend for production deployment

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Backend Production Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if we're in the backend directory
if (-not (Test-Path "artisan")) {
    Write-Host "Error: This script must be run from the backend directory" -ForegroundColor Red
    Write-Host "Please run: cd backend" -ForegroundColor Yellow
    exit 1
}

# Check for required tools
Write-Host "Checking prerequisites..." -ForegroundColor Yellow

# Check PHP
try {
    $phpVersion = php -v 2>&1 | Select-String -Pattern "PHP (\d+\.\d+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }
    if ([version]$phpVersion -lt [version]"8.2") {
        Write-Host "Error: PHP 8.2+ required. Found: PHP $phpVersion" -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ PHP $phpVersion found" -ForegroundColor Green
} catch {
    Write-Host "Error: PHP not found. Please install PHP 8.2+" -ForegroundColor Red
    exit 1
}

# Check Composer
try {
    composer --version | Out-Null
    Write-Host "✓ Composer found" -ForegroundColor Green
} catch {
    Write-Host "Error: Composer not found. Please install Composer" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 1: Install dependencies
Write-Host "Step 1: Installing production dependencies..." -ForegroundColor Yellow
composer install --optimize-autoloader --no-dev --no-interaction
if ($LASTEXITCODE -ne 0) {
    Write-Host "Error: Failed to install dependencies" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Dependencies installed" -ForegroundColor Green
Write-Host ""

# Step 2: Check .env file
Write-Host "Step 2: Checking environment configuration..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Write-Host "Creating .env from .env.example..." -ForegroundColor Yellow
        Copy-Item ".env.example" ".env"
        Write-Host "✓ .env file created" -ForegroundColor Green
        Write-Host "⚠ IMPORTANT: Please configure .env with production values before continuing!" -ForegroundColor Yellow
        Write-Host ""
        $continue = Read-Host "Have you configured .env? (y/n)"
        if ($continue -ne "y" -and $continue -ne "Y") {
            Write-Host "Deployment cancelled. Please configure .env and run again." -ForegroundColor Yellow
            exit 0
        }
    } else {
        Write-Host "Error: .env file not found and .env.example not available" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✓ .env file exists" -ForegroundColor Green
}

# Step 3: Generate application key
Write-Host "Step 3: Generating application key..." -ForegroundColor Yellow
php artisan key:generate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "Warning: Failed to generate key (may already exist)" -ForegroundColor Yellow
}
Write-Host "✓ Application key ready" -ForegroundColor Green
Write-Host ""

# Step 4: Run migrations
Write-Host "Step 4: Running database migrations..." -ForegroundColor Yellow
$runMigrations = Read-Host "Run migrations? This will modify the database. (y/n)"
if ($runMigrations -eq "y" -or $runMigrations -eq "Y") {
    php artisan migrate --force
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error: Migration failed" -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ Migrations completed" -ForegroundColor Green
} else {
    Write-Host "⚠ Skipping migrations" -ForegroundColor Yellow
}
Write-Host ""

# Step 5: Create storage link
Write-Host "Step 5: Creating storage symlink..." -ForegroundColor Yellow
php artisan storage:link
if ($LASTEXITCODE -ne 0) {
    Write-Host "Warning: Storage link may already exist" -ForegroundColor Yellow
} else {
    Write-Host "✓ Storage link created" -ForegroundColor Green
}
Write-Host ""

# Step 6: Clear and cache configuration
Write-Host "Step 6: Optimizing for production..." -ForegroundColor Yellow
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

Write-Host "✓ Configuration cached" -ForegroundColor Green
Write-Host ""

# Step 7: Set permissions (if on Linux/WSL)
if ($IsLinux -or (Get-Command "chmod" -ErrorAction SilentlyContinue)) {
    Write-Host "Step 7: Setting file permissions..." -ForegroundColor Yellow
    # Note: These commands may not work on Windows, but are safe to try
    try {
        chmod -R 775 storage bootstrap/cache 2>&1 | Out-Null
        Write-Host "✓ Permissions set" -ForegroundColor Green
    } catch {
        Write-Host "⚠ Could not set permissions (may require manual setup)" -ForegroundColor Yellow
    }
} else {
    Write-Host "Step 7: Skipping permissions (Windows environment)" -ForegroundColor Yellow
    Write-Host "⚠ On Linux server, run: chmod -R 775 storage bootstrap/cache" -ForegroundColor Yellow
}
Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deployment Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Verify .env configuration (APP_ENV=production, APP_DEBUG=false)" -ForegroundColor White
Write-Host "2. Set up web server (Nginx/Apache) to point to public/ directory" -ForegroundColor White
Write-Host "3. Configure SSL certificate" -ForegroundColor White
Write-Host "4. Set up cron job: * * * * * cd /path/to/backend && php artisan schedule:run" -ForegroundColor White
Write-Host "5. Test the API endpoints" -ForegroundColor White
Write-Host ""
Write-Host "For detailed instructions, see DEPLOYMENT_GUIDE.md" -ForegroundColor Cyan
Write-Host ""

