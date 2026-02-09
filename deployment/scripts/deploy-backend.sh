#!/bin/bash

# Goodwill Staffing - Backend Deployment Script (Linux/Mac)
# This script prepares the Laravel backend for production deployment

set -e  # Exit on error

echo "========================================"
echo "Backend Production Deployment"
echo "========================================"
echo ""

# Check if we're in the backend directory
if [ ! -f "artisan" ]; then
    echo "Error: This script must be run from the backend directory"
    echo "Please run: cd backend"
    exit 1
fi

# Check for required tools
echo "Checking prerequisites..."

# Check PHP
if ! command -v php &> /dev/null; then
    echo "Error: PHP not found. Please install PHP 8.2+"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;' | cut -d. -f1,2)
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]); then
    echo "Error: PHP 8.2+ required. Found: PHP $PHP_VERSION"
    exit 1
fi

echo "✓ PHP $PHP_VERSION found"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "Error: Composer not found. Please install Composer"
    exit 1
fi

echo "✓ Composer found"
echo ""

# Step 1: Install dependencies
echo "Step 1: Installing production dependencies..."
composer install --optimize-autoloader --no-dev --no-interaction
echo "✓ Dependencies installed"
echo ""

# Step 2: Check .env file
echo "Step 2: Checking environment configuration..."
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "Creating .env from .env.example..."
        cp .env.example .env
        echo "✓ .env file created"
        echo "⚠ IMPORTANT: Please configure .env with production values before continuing!"
        echo ""
        read -p "Have you configured .env? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "Deployment cancelled. Please configure .env and run again."
            exit 0
        fi
    else
        echo "Error: .env file not found and .env.example not available"
        exit 1
    fi
else
    echo "✓ .env file exists"
fi

# Step 3: Generate application key
echo "Step 3: Generating application key..."
php artisan key:generate --force || echo "⚠ Key may already exist"
echo "✓ Application key ready"
echo ""

# Step 4: Run migrations
echo "Step 4: Running database migrations..."
read -p "Run migrations? This will modify the database. (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo "✓ Migrations completed"
else
    echo "⚠ Skipping migrations"
fi
echo ""

# Step 5: Create storage link
echo "Step 5: Creating storage symlink..."
php artisan storage:link || echo "⚠ Storage link may already exist"
echo "✓ Storage link created"
echo ""

# Step 6: Clear and cache configuration
echo "Step 6: Optimizing for production..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ Configuration cached"
echo ""

# Step 7: Set permissions
echo "Step 7: Setting file permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || echo "⚠ Could not change ownership (may require sudo)"
echo "✓ Permissions set"
echo ""

# Summary
echo "========================================"
echo "Deployment Complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Verify .env configuration (APP_ENV=production, APP_DEBUG=false)"
echo "2. Set up web server (Nginx/Apache) to point to public/ directory"
echo "3. Configure SSL certificate"
echo "4. Set up cron job: * * * * * cd /path/to/backend && php artisan schedule:run"
echo "5. Test the API endpoints"
echo ""
echo "For detailed instructions, see DEPLOYMENT_GUIDE.md"
echo ""

