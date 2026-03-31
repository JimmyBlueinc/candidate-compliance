# Shared Hosting Deployment Guide

This guide covers deploying the Goodwill Staffing application to shared hosting providers (cPanel, Plesk, etc.) using file manager interfaces.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Understanding Shared Hosting Structure](#understanding-shared-hosting-structure)
3. [Backend Deployment](#backend-deployment)
4. [Frontend Deployment](#frontend-deployment)
5. [Database Setup](#database-setup)
6. [Configuration](#configuration)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### What You Need

- Shared hosting account with:
  - PHP 8.2+ support
  - MySQL database
  - File manager access (cPanel, Plesk, etc.)
  - SSH/Terminal access (optional but recommended)
  - Composer support (check with your host)
  - Node.js support (for building frontend, or build locally)

### Check Your Hosting

**Required PHP Extensions:**
- pdo
- pdo_mysql
- mbstring
- openssl
- tokenizer
- xml
- json
- bcmath
- fileinfo
- gd

**Check via cPanel:**
1. Go to "Select PHP Version" or "PHP Configuration"
2. Verify PHP 8.2+ is available
3. Enable required extensions

---

## Understanding Shared Hosting Structure

### Typical Directory Structure

```
/home/username/
├── public_html/          # Main website (frontend goes here)
│   └── index.html
├── public_html/api/     # Backend API (alternative location)
└── backend/             # Backend files (outside public_html)
    └── public/          # Backend public directory
```

**Important:** The backend's `public/` folder should be accessible via web, but the rest of the backend should be outside the public directory for security.

### Recommended Structure

**Option 1: Separate Domains/Subdomains**
```
public_html/              # Frontend (yourdomain.com)
api.yourdomain.com/       # Backend API (subdomain)
```

**Option 2: Subdirectory**
```
public_html/              # Frontend (yourdomain.com)
public_html/api/          # Backend API (yourdomain.com/api)
```

**Option 3: Outside Public (Most Secure)**
```
/home/username/
├── public_html/          # Frontend
├── backend/              # Backend (outside public)
│   └── public/          # Symlink or copy to public_html/api
```

---

## Backend Deployment

### Method 1: Using File Manager + Terminal (Recommended)

#### Step 1: Prepare Backend Locally

On your local machine:

```bash
cd backend

# Install dependencies
composer install --optimize-autoloader --no-dev

# Create a deployment package
# Exclude unnecessary files
```

Create a `.htaccess` file in `backend/public/` if it doesn't exist:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

#### Step 2: Upload Files

**Via File Manager:**
1. Log into cPanel/Plesk
2. Open File Manager
3. Navigate to your domain's root or create `backend` folder
4. Upload backend files (excluding `vendor` if you'll install via terminal)
5. Upload structure:
   ```
   backend/
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── database/
   ├── public/          # This will be web-accessible
   ├── resources/
   ├── routes/
   ├── storage/
   ├── .env
   ├── artisan
   └── composer.json
   ```

**Via FTP:**
- Use FileZilla or similar FTP client
- Connect to your hosting
- Upload backend folder structure

#### Step 3: Install Dependencies (Terminal/SSH)

If your host provides SSH access:

```bash
# Connect via SSH
ssh username@yourdomain.com

# Navigate to backend
cd ~/backend  # or wherever you uploaded

# Install dependencies
composer install --optimize-autoloader --no-dev

# If composer not available, install it:
# curl -sS https://getcomposer.org/installer | php
# php composer.phar install --optimize-autoloader --no-dev
```

**If no SSH access:**
- Install dependencies locally
- Upload the `vendor/` folder via File Manager/FTP

#### Step 4: Configure Environment

**Via File Manager:**
1. Navigate to `backend/` folder
2. Create or edit `.env` file
3. Add production configuration (see below)

**Via Terminal:**
```bash
cd ~/backend
cp .env.example .env
nano .env  # or use file manager
```

**Production .env Configuration:**
```env
APP_NAME="Goodwill Staffing Compliance Tracker"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
# Or if using subdirectory: https://yourdomain.com/api

# Generate key via terminal:
# php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=localhost  # Usually 'localhost' on shared hosting
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

FRONTEND_URL=https://yourdomain.com

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com  # Or your email provider
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
```

#### Step 5: Set Up Public Directory

**Option A: Subdomain (Recommended)**

1. Create subdomain `api.yourdomain.com` in cPanel
2. Point it to `backend/public/` directory
3. Or create symlink:
   ```bash
   ln -s ~/backend/public ~/public_html/api
   ```

**Option B: Subdirectory**

1. Copy `backend/public/` contents to `public_html/api/`
2. Update `.env` `APP_URL` to `https://yourdomain.com/api`
3. Update `public_html/api/index.php`:
   ```php
   require __DIR__.'/../../backend/vendor/autoload.php';
   $app = require_once __DIR__.'/../../backend/bootstrap/app.php';
   ```

**Option C: Direct Public Access**

1. Point domain/subdomain directly to `backend/public/`
2. Ensure parent directories are not web-accessible

#### Step 6: Run Migrations

**Via Terminal:**
```bash
cd ~/backend
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**If no terminal access:**
- Some hosts provide "Run PHP Script" in cPanel
- Or use a temporary migration script (see below)

#### Step 7: Set Permissions

**Via File Manager:**
1. Right-click `backend/storage/` folder
2. Set permissions to `775`
3. Set permissions for `backend/bootstrap/cache/` to `775`

**Via Terminal:**
```bash
chmod -R 775 storage bootstrap/cache
```

---

## Frontend Deployment

### Method 1: Build Locally, Upload Build

#### Step 1: Build Locally

On your local machine:

```bash
cd frontend

# Create production environment file
echo "VITE_API_BASE_URL=https://api.yourdomain.com/api" > .env.production
# Or if using subdirectory:
# echo "VITE_API_BASE_URL=https://yourdomain.com/api" > .env.production

# Install and build
npm install
npm run build
```

This creates a `dist/` folder with production files.

#### Step 2: Upload Build

**Via File Manager:**
1. Open File Manager in cPanel
2. Navigate to `public_html/`
3. Delete existing files (backup first!)
4. Upload all contents from `frontend/dist/` folder
5. Ensure `index.html` is in the root

**Via FTP:**
- Upload all files from `dist/` to `public_html/`

#### Step 3: Configure SPA Routing

Create `.htaccess` in `public_html/` (for Apache):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Handle Angular/React Router
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
```

For Nginx (if your host uses it), contact support for configuration.

### Method 2: Build on Server (If Node.js Available)

Some shared hosts provide Node.js:

```bash
# Via SSH/Terminal
cd ~/public_html
git clone <your-repo> temp-build
cd temp-build/frontend

# Create .env.production
echo "VITE_API_BASE_URL=https://api.yourdomain.com/api" > .env.production

# Build
npm install
npm run build

# Copy build files
cp -r dist/* ~/public_html/

# Cleanup
cd ~
rm -rf temp-build
```

---

## Database Setup

### Via cPanel MySQL Database Wizard

1. **Create Database:**
   - Go to "MySQL Databases" in cPanel
   - Create new database: `goodwill_prod`
   - Note the full database name (usually `username_goodwill_prod`)

2. **Create User:**
   - Create MySQL user
   - Set strong password
   - Note the full username (usually `username_dbuser`)

3. **Assign User to Database:**
   - Add user to database
   - Grant ALL PRIVILEGES

4. **Update .env:**
   ```env
   DB_DATABASE=username_goodwill_prod
   DB_USERNAME=username_dbuser
   DB_PASSWORD=your_password
   ```

### Import Database (If Needed)

**Via phpMyAdmin:**
1. Go to phpMyAdmin in cPanel
2. Select your database
3. Click "Import"
4. Upload SQL file (if you have one)

**Via Terminal:**
```bash
mysql -u username_dbuser -p username_goodwill_prod < database.sql
```

---

## Configuration

### Backend Configuration

#### 1. Application Key

**Via Terminal:**
```bash
cd ~/backend
php artisan key:generate
```

**Via File Manager:**
- Edit `.env` manually
- Generate key locally and paste:
  ```bash
  php artisan key:generate --show
  ```

#### 2. Storage Link

**Via Terminal:**
```bash
cd ~/backend
php artisan storage:link
```

**Manual (File Manager):**
- Create symlink from `backend/storage/app/public` to `backend/public/storage`
- Or copy files manually (not recommended)

#### 3. Cache Configuration

**Via Terminal:**
```bash
cd ~/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Clear Cache:**
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Frontend Configuration

#### API URL Configuration

The API URL is set during build in `.env.production`:

```env
VITE_API_BASE_URL=https://api.yourdomain.com/api
```

**If you need to change it:**
1. Update `.env.production`
2. Rebuild: `npm run build`
3. Re-upload `dist/` folder

---

## Cron Jobs Setup

### Via cPanel Cron Jobs

1. Go to "Cron Jobs" in cPanel
2. Add new cron job:
   - **Minute:** `*`
   - **Hour:** `*`
   - **Day:** `*`
   - **Month:** `*`
   - **Weekday:** `*`
   - **Command:**
     ```bash
     /usr/bin/php /home/username/backend/artisan schedule:run >> /dev/null 2>&1
     ```
   - Or use full PHP path (check with your host)

### Alternative: Web-Based Cron

If cron jobs aren't available, use a web-based cron service:
- Set up URL: `https://api.yourdomain.com/api/cron` (create route)
- Use services like cron-job.org, EasyCron, etc.

---

## Security Considerations

### 1. Protect Backend Files

Ensure backend files (except `public/`) are not web-accessible:

**Via .htaccess in backend root:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ - [F,L]
</IfModule>
```

### 2. Secure .env File

**Via .htaccess:**
```apache
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

### 3. Disable Directory Listing

**In public_html/.htaccess:**
```apache
Options -Indexes
```

### 4. Set Proper Permissions

- Files: `644`
- Folders: `755`
- Storage: `775`
- `.env`: `600` (if possible)

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

**Check:**
- PHP version (should be 8.2+)
- PHP extensions enabled
- File permissions
- `.env` file exists and is configured
- Error logs in cPanel

**Fix:**
```bash
# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Re-cache
php artisan config:cache
```

#### 2. Database Connection Error

**Check:**
- Database credentials in `.env`
- Database host (usually `localhost` on shared hosting)
- Database user has proper permissions
- Database exists

#### 3. File Upload Not Working

**Check:**
- Storage link exists: `php artisan storage:link`
- Storage folder permissions: `775`
- `upload_max_filesize` in PHP settings
- `post_max_size` in PHP settings

#### 4. CORS Errors

**Check:**
- `FRONTEND_URL` in backend `.env` matches frontend domain
- CORS configuration in `backend/config/cors.php`
- Clear config cache: `php artisan config:clear`

#### 5. Routes Not Working (Frontend)

**Check:**
- `.htaccess` file exists in `public_html/`
- `mod_rewrite` is enabled
- Server supports `.htaccess` files

#### 6. Composer Not Available

**Solutions:**
- Install Composer locally, upload `vendor/` folder
- Use host's Composer (if available)
- Contact host support

### Getting Help

1. **Check Error Logs:**
   - cPanel → Error Log
   - `backend/storage/logs/laravel.log`

2. **Enable Debugging (Temporarily):**
   ```env
   APP_DEBUG=true
   ```
   - **Remember to disable in production!**

3. **Contact Host Support:**
   - PHP version requirements
   - Composer availability
   - SSH access
   - File permissions

---

## Quick Checklist

### Backend
- [ ] Backend files uploaded
- [ ] Dependencies installed (`vendor/` folder)
- [ ] `.env` file configured
- [ ] Application key generated
- [ ] Database created and configured
- [ ] Migrations run
- [ ] Storage link created
- [ ] Permissions set (storage, cache)
- [ ] Configuration cached
- [ ] Public directory accessible
- [ ] `.htaccess` configured

### Frontend
- [ ] Frontend built locally
- [ ] `dist/` folder uploaded to `public_html/`
- [ ] `.htaccess` for SPA routing created
- [ ] API URL configured correctly
- [ ] All assets loading correctly

### Configuration
- [ ] Database connection working
- [ ] API endpoints accessible
- [ ] CORS configured
- [ ] File uploads working
- [ ] Email configured (if needed)
- [ ] Cron job set up

### Security
- [ ] `APP_DEBUG=false` in production
- [ ] `.env` file protected
- [ ] Backend files not web-accessible
- [ ] SSL certificate installed
- [ ] Strong passwords set

---

## Alternative: One-Click Install Script

If you have SSH access, you can create a simple install script:

**Create `install.sh` in backend:**
```bash
#!/bin/bash
composer install --optimize-autoloader --no-dev
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
echo "Installation complete!"
```

Run: `bash install.sh`

---

## Support Resources

- **Laravel Documentation:** https://laravel.com/docs
- **cPanel Documentation:** Your host's cPanel docs
- **Error Logs:** Check cPanel error logs and `storage/logs/laravel.log`

---

**Need more help?** Check `DEPLOYMENT_STEPS.md` for general deployment guidance.

