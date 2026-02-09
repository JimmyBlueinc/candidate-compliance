# Goodwill Staffing - Shared Hosting Upload Script
# This script prepares files for upload to shared hosting via File Manager/FTP

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Shared Hosting Upload Preparation" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$uploadDir = "upload-package"
$backendDir = Join-Path $uploadDir "backend"
$frontendDir = Join-Path $uploadDir "frontend-dist"

# Create upload directory
if (Test-Path $uploadDir) {
    Write-Host "Cleaning existing upload directory..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force $uploadDir
}

New-Item -ItemType Directory -Path $uploadDir -Force | Out-Null
New-Item -ItemType Directory -Path $backendDir -Force | Out-Null
New-Item -ItemType Directory -Path $frontendDir -Force | Out-Null

Write-Host "Created upload directories" -ForegroundColor Green
Write-Host ""

# Step 1: Prepare Backend
Write-Host "Step 1: Preparing backend files..." -ForegroundColor Yellow

# Copy backend files (excluding unnecessary ones)
$backendExclude = @(
    "node_modules",
    ".git",
    ".env",
    "tests",
    ".phpunit.result.cache",
    "phpunit.xml"
)

# Copy directory structure and files
Get-ChildItem -Path "backend" -Recurse | ForEach-Object {
    $relativePath = $_.FullName.Replace((Resolve-Path "backend").Path, "").TrimStart("\")
    
    # Skip excluded items
    $shouldExclude = $false
    foreach ($exclude in $backendExclude) {
        if ($relativePath -like "*$exclude*" -or $relativePath.StartsWith($exclude)) {
            $shouldExclude = $true
            break
        }
    }
    
    # Skip cache and log files
    if ($relativePath -like "*storage\logs\*" -or 
        $relativePath -like "*storage\framework\cache\*" -or
        $relativePath -like "*storage\framework\sessions\*" -or
        $relativePath -like "*storage\framework\views\*") {
        $shouldExclude = $true
    }
    
    if (-not $shouldExclude) {
        $targetPath = Join-Path $backendDir $relativePath
        
        if ($_.PSIsContainer) {
            if (-not (Test-Path $targetPath)) {
                New-Item -ItemType Directory -Path $targetPath -Force | Out-Null
            }
        } else {
            $targetDir = Split-Path $targetPath -Parent
            if (-not (Test-Path $targetDir)) {
                New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
            }
            Copy-Item $_.FullName -Destination $targetPath -Force
        }
    }
}

Write-Host "Backend files prepared" -ForegroundColor Green
Write-Host ""

# Step 2: Check if vendor folder exists
Write-Host "Step 2: Checking dependencies..." -ForegroundColor Yellow
if (Test-Path (Join-Path "backend" "vendor")) {
    Write-Host "Vendor folder found - will be included" -ForegroundColor Green
} else {
    Write-Host "Vendor folder not found" -ForegroundColor Yellow
    Write-Host "  You will need to install dependencies on the server" -ForegroundColor Gray
    Write-Host "  cd backend; composer install --optimize-autoloader --no-dev" -ForegroundColor Gray
}
Write-Host ""

# Step 3: Create .env.example for backend
Write-Host "Step 3: Creating .env.example..." -ForegroundColor Yellow
$envExamplePath = Join-Path $backendDir ".env.example"
$envContent = @"
APP_NAME="Goodwill Staffing Compliance Tracker"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

# Generate key with: php artisan key:generate
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

FRONTEND_URL=https://yourdomain.com

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
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
"@

[System.IO.File]::WriteAllText($envExamplePath, $envContent, [System.Text.Encoding]::UTF8)
Write-Host ".env.example created" -ForegroundColor Green
Write-Host ""

# Step 4: Build Frontend
Write-Host "Step 4: Building frontend..." -ForegroundColor Yellow

if (-not (Test-Path (Join-Path "frontend" "dist"))) {
    Write-Host "Frontend not built. Building now..." -ForegroundColor Yellow
    
    Push-Location frontend
    
    # Check for .env.production
    if (-not (Test-Path ".env.production")) {
        Write-Host "Creating .env.production..." -ForegroundColor Yellow
        $apiUrl = Read-Host "Enter your production API URL (e.g., https://api.yourdomain.com/api)"
        "VITE_API_BASE_URL=$apiUrl" | Out-File -FilePath ".env.production" -Encoding utf8
    }
    
    npm install
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error: npm install failed" -ForegroundColor Red
        Pop-Location
        exit 1
    }
    
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Error: Build failed" -ForegroundColor Red
        Pop-Location
        exit 1
    }
    
    Pop-Location
}

# Copy frontend dist
$frontendDistPath = Join-Path "frontend" "dist"
if (Test-Path $frontendDistPath) {
    Copy-Item -Path (Join-Path $frontendDistPath "*") -Destination $frontendDir -Recurse -Force
    Write-Host "Frontend build copied" -ForegroundColor Green
} else {
    Write-Host "Error: Frontend dist folder not found" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 5: Create .htaccess for frontend
Write-Host "Step 5: Creating frontend .htaccess..." -ForegroundColor Yellow
$htaccessPath = Join-Path $frontendDir ".htaccess"
$htaccessContent = @"
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
"@

[System.IO.File]::WriteAllText($htaccessPath, $htaccessContent, [System.Text.Encoding]::UTF8)
Write-Host "Frontend .htaccess created" -ForegroundColor Green
Write-Host ""

# Step 6: Create upload instructions
Write-Host "Step 6: Creating upload instructions..." -ForegroundColor Yellow

$instructionsPath = Join-Path $uploadDir "UPLOAD_INSTRUCTIONS.txt"
$instructionsContent = @"
# Shared Hosting Upload Instructions

## Backend Upload

1. Log into your hosting File Manager (cPanel, Plesk, etc.)
2. Navigate to your domain root (usually /home/username/)
3. Create a backend folder (outside public_html if possible)
4. Upload all files from backend folder to your hosting backend folder
5. Structure should be:
   /home/username/backend/
   - app/
   - public/
   - storage/
   - ... (all other files)

6. Create .env file in backend folder:
   - Copy .env.example to .env
   - Edit with your production values
   - Set APP_KEY (run: php artisan key:generate)

7. Set permissions:
   - storage/ folder: 775
   - bootstrap/cache/ folder: 775

8. Make backend accessible:
   Option A: Create subdomain api.yourdomain.com pointing to backend/public/
   Option B: Copy backend/public/ contents to public_html/api/

9. Run commands (if terminal/SSH available):
   cd backend
   composer install --optimize-autoloader --no-dev
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache

## Frontend Upload

1. Log into File Manager
2. Navigate to public_html/ (or your domain root)
3. Delete existing files (backup first!)
4. Upload all files from frontend-dist folder to public_html/
5. Ensure index.html is in the root

## Database Setup

1. Go to MySQL Databases in cPanel
2. Create database and user
3. Add user to database with ALL PRIVILEGES
4. Update .env with database credentials

## Cron Job Setup

1. Go to Cron Jobs in cPanel
2. Add new cron:
   - Schedule: * * * * * (every minute)
   - Command: /usr/bin/php /home/username/backend/artisan schedule:run

## Testing

- Frontend: https://yourdomain.com
- Backend API: https://api.yourdomain.com/api/health

For detailed instructions, see SHARED_HOSTING_DEPLOYMENT.md
"@

[System.IO.File]::WriteAllText($instructionsPath, $instructionsContent, [System.Text.Encoding]::UTF8)
Write-Host "Instructions created" -ForegroundColor Green
Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Upload Package Ready!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Package location: $uploadDir" -ForegroundColor Yellow
Write-Host ""
Write-Host "Contents:" -ForegroundColor Yellow
Write-Host "  - backend/          -> Upload to your hosting backend folder" -ForegroundColor White
Write-Host "  - frontend-dist/    -> Upload to public_html/" -ForegroundColor White
Write-Host "  - UPLOAD_INSTRUCTIONS.txt" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Review UPLOAD_INSTRUCTIONS.txt" -ForegroundColor White
Write-Host "2. Upload backend/ folder via File Manager or FTP" -ForegroundColor White
Write-Host "3. Upload frontend-dist/ contents to public_html/" -ForegroundColor White
Write-Host "4. Configure .env file on server" -ForegroundColor White
Write-Host "5. Set up database in cPanel" -ForegroundColor White
Write-Host "6. Run migrations and setup commands" -ForegroundColor White
Write-Host ""
Write-Host "For detailed instructions, see SHARED_HOSTING_DEPLOYMENT.md" -ForegroundColor Cyan
Write-Host ""
