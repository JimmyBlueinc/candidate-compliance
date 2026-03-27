# Shared Hosting Quick Start Guide

Quick reference for deploying to shared hosting (cPanel, Plesk, etc.).

## 🚀 Quick Steps

### 1. Prepare Backend Locally

```bash
cd backend
composer install --optimize-autoloader --no-dev
```

### 2. Upload Backend Files

**Via File Manager:**
- Upload entire `backend/` folder to your hosting
- Place it outside `public_html/` if possible (more secure)

**Structure:**
```
/home/username/
├── backend/          # Backend files
│   └── public/      # This will be web-accessible
└── public_html/     # Frontend goes here
```

### 3. Configure Backend

**Create `.env` file in `backend/` folder:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

FRONTEND_URL=https://yourdomain.com
```

**Via Terminal (if available):**
```bash
cd ~/backend
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
```

### 4. Set Up Database

**Via cPanel:**
1. Go to "MySQL Databases"
2. Create database and user
3. Add user to database with ALL PRIVILEGES
4. Update `.env` with credentials

### 5. Make Backend Accessible

**Option A: Subdomain (Recommended)**
- Create subdomain `api.yourdomain.com`
- Point to `backend/public/` directory

**Option B: Subdirectory**
- Copy `backend/public/` contents to `public_html/api/`
- Update `APP_URL` in `.env` to `https://yourdomain.com/api`

### 6. Build and Upload Frontend

**Locally:**
```bash
cd frontend
echo "VITE_API_BASE_URL=https://api.yourdomain.com/api" > .env.production
npm install
npm run build
```

**Upload:**
- Upload all files from `frontend/dist/` to `public_html/`

### 7. Configure Frontend Routing

**Create `.htaccess` in `public_html/`:**

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

### 8. Set Permissions

**Via File Manager:**
- `backend/storage/` → 775
- `backend/bootstrap/cache/` → 775

**Via Terminal:**
```bash
chmod -R 775 backend/storage backend/bootstrap/cache
```

### 9. Set Up Cron Job

**Via cPanel Cron Jobs:**
- Command: `/usr/bin/php /home/username/backend/artisan schedule:run`
- Schedule: Every minute (`* * * * *`)

### 10. Test

- Frontend: `https://yourdomain.com`
- Backend API: `https://api.yourdomain.com/api/health`

---

## 📋 Essential Files to Upload

### Backend
```
backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          # Web-accessible
├── resources/
├── routes/
├── storage/         # Set permissions to 775
├── vendor/          # Or install via composer on server
├── .env            # Configure with production values
├── artisan
└── composer.json
```

### Frontend
```
public_html/
├── index.html
├── assets/         # JS, CSS, images
└── .htaccess       # For SPA routing
```

---

## ⚙️ Quick Configuration

### Backend .env (Minimum Required)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
APP_KEY=base64:...  # Generate with: php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=username_dbname
DB_USERNAME=username_dbuser
DB_PASSWORD=your_password

FRONTEND_URL=https://yourdomain.com
```

### Frontend Build

```bash
# Create .env.production
echo "VITE_API_BASE_URL=https://api.yourdomain.com/api" > .env.production

# Build
npm run build

# Upload dist/ contents to public_html/
```

---

## 🔧 Common Commands (If Terminal Available)

```bash
# Backend
cd ~/backend
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache

# Clear caches (if issues)
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

---

## 🆘 Quick Troubleshooting

### 500 Error
- Check PHP version (8.2+)
- Check file permissions
- Check `.env` file exists
- Check error logs in cPanel

### Database Error
- Verify credentials in `.env`
- Check database exists in cPanel
- Verify user has permissions

### Frontend Not Loading
- Check `.htaccess` exists
- Verify `mod_rewrite` enabled
- Check API URL in build

### File Upload Not Working
- Run: `php artisan storage:link`
- Check storage permissions (775)
- Check PHP upload limits

---

## 📞 Need More Help?

- **Detailed Guide:** See `SHARED_HOSTING_DEPLOYMENT.md`
- **General Deployment:** See `DEPLOYMENT_STEPS.md`
- **Error Logs:** Check cPanel Error Log and `backend/storage/logs/laravel.log`

---

## ✅ Final Checklist

- [ ] Backend uploaded and configured
- [ ] Database created and connected
- [ ] Frontend built and uploaded
- [ ] API accessible (test `/api/health`)
- [ ] Frontend loads correctly
- [ ] Login/registration works
- [ ] File uploads work
- [ ] Cron job configured
- [ ] SSL certificate installed
- [ ] `APP_DEBUG=false` in production

