# Step-by-Step Deployment Guide

This guide provides detailed instructions for deploying both the frontend and backend of the Goodwill Staffing Compliance Tracker application.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Backend Deployment](#backend-deployment)
3. [Frontend Deployment](#frontend-deployment)
4. [Web Server Configuration](#web-server-configuration)
5. [Post-Deployment](#post-deployment)
6. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Server Requirements

**Backend:**
- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or PostgreSQL 13+
- Web server (Nginx or Apache)
- SSL certificate (recommended)

**Frontend:**
- Node.js 18+ and npm (for building)
- Web server or static hosting service

### Required PHP Extensions

```bash
php -m | grep -E "pdo|pdo_mysql|mbstring|openssl|tokenizer|xml|json|bcmath|fileinfo|gd"
```

If any are missing, install them:
```bash
# Ubuntu/Debian
sudo apt-get install php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-gd

# CentOS/RHEL
sudo yum install php82-mysql php82-mbstring php82-xml php82-bcmath php82-gd
```

---

## Backend Deployment

### Option 1: Using Deployment Script (Recommended)

**Windows (PowerShell):**
```powershell
cd backend
.\..\deploy-backend.ps1
```

**Linux/Mac:**
```bash
cd backend
chmod +x ../deploy-backend.sh
../deploy-backend.sh
```

### Option 2: Manual Deployment

#### Step 1: Clone and Navigate
```bash
git clone <your-repository-url>
cd goodwill/backend
```

#### Step 2: Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

#### Step 3: Configure Environment
```bash
cp .env.example .env
nano .env  # or use your preferred editor
```

**Required .env settings for production:**
```env
APP_NAME="Goodwill Staffing Compliance Tracker"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
APP_KEY=base64:your-generated-key-here

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=goodwill_production
DB_USERNAME=your_db_user
DB_PASSWORD=your_strong_password

# Frontend URL (for CORS)
FRONTEND_URL=https://yourdomain.com

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Goodwill Staffing"

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Sanctum
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

#### Step 4: Generate Application Key
```bash
php artisan key:generate
```

#### Step 5: Run Migrations
```bash
php artisan migrate --force
```

#### Step 6: Create Storage Link
```bash
php artisan storage:link
```

#### Step 7: Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 8: Set Permissions (Linux)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### Step 9: Set Up Cron Job
```bash
crontab -e
```

Add this line:
```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## Frontend Deployment

### Option 1: Using Deployment Script (Recommended)

**Windows (PowerShell):**
```powershell
cd frontend
.\..\deploy-frontend.ps1
```

**Linux/Mac:**
```bash
cd frontend
chmod +x ../deploy-frontend.sh
../deploy-frontend.sh
```

### Option 2: Manual Deployment

#### Step 1: Navigate to Frontend
```bash
cd frontend
```

#### Step 2: Create Production Environment File
```bash
echo "VITE_API_BASE_URL=https://api.yourdomain.com/api" > .env.production
```

#### Step 3: Install Dependencies
```bash
npm install
```

#### Step 4: Build for Production
```bash
npm run build
```

This creates a `dist/` folder with optimized production files.

### Deployment Options

#### Option A: Static Hosting (Vercel, Netlify, etc.)

**Vercel:**
1. Install Vercel CLI: `npm i -g vercel`
2. Run: `vercel`
3. Or connect your GitHub repository on vercel.com
4. Set environment variable: `VITE_API_BASE_URL=https://api.yourdomain.com/api`
5. Build command: `npm run build`
6. Output directory: `dist`

**Netlify:**
1. Connect your repository on netlify.com
2. Build command: `npm run build`
3. Publish directory: `dist`
4. Add environment variable: `VITE_API_BASE_URL=https://api.yourdomain.com/api`

**Cloudflare Pages:**
1. Connect your repository
2. Build command: `npm run build`
3. Build output directory: `dist`
4. Add environment variable: `VITE_API_BASE_URL`

#### Option B: Traditional Web Server

Copy the contents of `dist/` to your web server root directory.

#### Option C: AWS S3 + CloudFront

1. Create an S3 bucket
2. Upload `dist/` contents to the bucket
3. Enable static website hosting
4. Set up CloudFront distribution
5. Configure error pages to redirect to `index.html` (for SPA routing)

---

## Web Server Configuration

### Nginx Configuration

#### Backend API Server

Create `/etc/nginx/sites-available/goodwill-api`:

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.yourdomain.com;
    
    root /var/www/goodwill/backend/public;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Logging
    access_log /var/log/nginx/goodwill-api-access.log;
    error_log /var/log/nginx/goodwill-api-error.log;
    
    # Main location block
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Deny access to storage and bootstrap
    location ~ ^/(storage|bootstrap) {
        deny all;
    }
    
    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/goodwill-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Frontend Server

Create `/etc/nginx/sites-available/goodwill-frontend`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    root /var/www/goodwill/frontend/dist;
    index index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # SPA Routing - serve index.html for all routes
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/goodwill-frontend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache Configuration

#### Backend (.htaccess already included)

Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Point Apache virtual host to `backend/public/` directory.

#### Frontend

Create `.htaccess` in `frontend/dist/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
```

---

## SSL Certificate Setup

### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Get certificate for backend
sudo certbot --nginx -d api.yourdomain.com

# Get certificate for frontend
sudo certbot --nginx -d yourdomain.com

# Auto-renewal is set up automatically
```

---

## Post-Deployment

### 1. Verify Backend

```bash
# Test health endpoint
curl https://api.yourdomain.com/api/health

# Should return: {"status":"ok"}
```

### 2. Verify Frontend

1. Open `https://yourdomain.com` in a browser
2. Test login functionality
3. Verify API calls are working
4. Check browser console for errors

### 3. Test Critical Features

- [ ] User registration
- [ ] User login
- [ ] Password reset
- [ ] File uploads (avatar, documents)
- [ ] API authentication
- [ ] Protected routes
- [ ] Email sending (if configured)

### 4. Set Up Monitoring

**Error Logging:**
```bash
# View Laravel logs
tail -f /var/www/goodwill/backend/storage/logs/laravel.log

# View Nginx logs
tail -f /var/log/nginx/goodwill-api-error.log
```

**Uptime Monitoring:**
- Use services like UptimeRobot, Pingdom, or StatusCake
- Monitor both frontend and API endpoints

### 5. Database Backups

Create a backup script `/usr/local/bin/backup-goodwill.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/goodwill"
DB_NAME="goodwill_production"
DB_USER="your_db_user"
DB_PASS="your_db_password"

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# File storage backup
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/goodwill/backend/storage/app

# Keep only last 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

Add to crontab:
```bash
0 2 * * * /usr/local/bin/backup-goodwill.sh
```

---

## Troubleshooting

### Backend Issues

**500 Internal Server Error:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Permission Denied:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Database Connection Error:**
- Verify `.env` database credentials
- Check database server is running
- Verify firewall allows connections

### Frontend Issues

**API Connection Errors:**
- Verify `VITE_API_BASE_URL` in `.env.production`
- Check CORS configuration in backend
- Verify backend is accessible from frontend domain

**404 on Routes:**
- Ensure web server is configured for SPA routing (try_files)
- Check `.htaccess` file exists (Apache)

**Build Errors:**
```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

### CORS Issues

If you see CORS errors:
1. Verify `FRONTEND_URL` in backend `.env` matches your frontend domain
2. Check `config/cors.php` allows your frontend origin
3. Clear config cache: `php artisan config:clear && php artisan config:cache`

---

## Quick Reference

### Backend Commands
```bash
# Deploy
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Frontend Commands
```bash
# Build
npm install
npm run build

# Preview build locally
npm run preview
```

---

## Support

For additional help:
- Check `DEPLOYMENT_GUIDE.md` for more details
- Review `PRODUCTION_CHECKLIST.md` for security checklist
- Check Laravel logs: `storage/logs/laravel.log`
- Check Nginx logs: `/var/log/nginx/`

