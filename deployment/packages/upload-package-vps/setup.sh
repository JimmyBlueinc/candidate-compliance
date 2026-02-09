#!/bin/bash

# Goodwill Staffing - VPS Setup Script
# Run this script on your VPS after extracting files

echo "========================================"
echo "Goodwill Staffing - VPS Setup"
echo "========================================"
echo ""

# Check if we're in the right directory
if [ ! -d "backend" ] || [ ! -d "api" ]; then
    echo "Error: Please run this script from your domain root folder"
    echo "Make sure you see 'backend' and 'api' folders"
    exit 1
fi

# Step 1: Create .env file
echo "Step 1: Setting up .env file..."
cd backend

if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✓ Created .env file from .env.example"
    else
        echo "⚠ .env.example not found, creating basic .env..."
        cat > .env << 'EOF'
APP_NAME="Goodwill Staffing Compliance Tracker"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com/api

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
EOF
        echo "✓ Created basic .env file"
    fi
else
    echo "✓ .env file already exists"
fi

echo ""
echo "⚠ IMPORTANT: Please edit backend/.env file with your actual values:"
echo "   - Database credentials"
echo "   - APP_URL (your domain)"
echo "   - FRONTEND_URL (your domain)"
echo "   - Mail settings"
echo ""

# Step 2: Generate application key
echo "Step 2: Generating application key..."
php artisan key:generate --force 2>/dev/null || echo "⚠ Could not generate key (PHP may not be in PATH)"
echo ""

# Step 3: Set permissions
echo "Step 3: Setting file permissions..."
chmod -R 755 backend
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
chmod -R 755 api
chmod -R 755 .
echo "✓ Permissions set"
echo ""

# Step 4: Create storage link
echo "Step 4: Creating storage link..."
php artisan storage:link 2>/dev/null || echo "⚠ Could not create storage link"
echo ""

# Step 5: Cache configuration
echo "Step 5: Caching configuration..."
php artisan config:cache 2>/dev/null || echo "⚠ Could not cache config"
php artisan route:cache 2>/dev/null || echo "⚠ Could not cache routes"
php artisan view:cache 2>/dev/null || echo "⚠ Could not cache views"
echo ""

# Summary
echo "========================================"
echo "Setup Complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Edit backend/.env with your database credentials"
echo "2. Create MySQL database in your control panel"
echo "3. Run: cd backend && php artisan migrate --force"
echo "4. Test your website: https://yourdomain.com"
echo "5. Test API: https://yourdomain.com/api/health"
echo ""

