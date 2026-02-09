# Complete Upload Package - Frontend + Backend + API

## ✅ Everything is Ready!

This folder contains:
- ✅ Frontend (with correct API URL: http://cpdemo.blueinctech.com/api)
- ✅ Backend API (api/ folder)
- ✅ Backend files (backend/ folder)
- ✅ All configuration files

## 📦 What's Inside

```
complete-upload/
│
├── index.html              ← Frontend homepage
├── .htaccess               ← Frontend routing (fixed)
├── assets/                 ← Frontend CSS/JS (updated)
├── vite.svg
│
├── api/                    ← Backend API endpoint
│   ├── index.php           ← Configured correctly
│   ├── .htaccess
│   └── ... (other API files)
│
└── backend/                ← Backend files
    ├── app/
    ├── storage/
    ├── vendor/             ← Dependencies included
    ├── .env.example
    └── ... (all files)
```

## 🚀 How to Upload

### Step 1: Compress This Folder

1. **Select this entire folder** (`complete-upload`)
2. **Right-click → Compress to ZIP** (or use 7-Zip/WinRAR)
3. **Name it:** `goodwill-complete.zip`

### Step 2: Upload to Your Domain

1. **Log into your VPS File Manager**
2. **Navigate to your domain root** (`cpdemo.blueinctech.com`)
3. **Upload `goodwill-complete.zip`**
4. **Extract it** - this will create all folders and files

### Step 3: Configure Backend

**Option A: Use setup.php (Easiest)**
1. Upload `setup.php` to domain root (if you have it)
2. Visit: `http://cpdemo.blueinctech.com/setup.php`
3. Fill in database credentials
4. Click "Create .env File"
5. Delete `setup.php` after setup

**Option B: Manual**
1. Go to `backend/` folder
2. Copy `.env.example` to `.env`
3. Edit `.env` with:
   ```
   APP_URL=http://cpdemo.blueinctech.com/api
   FRONTEND_URL=http://cpdemo.blueinctech.com
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   SESSION_SECURE_COOKIE=false
   SANCTUM_STATEFUL_DOMAINS=cpdemo.blueinctech.com
   ```

### Step 4: Set Permissions

**Via File Manager:**
- `backend/storage/` → Permissions → 775
- `backend/bootstrap/cache/` → Permissions → 775

**Via Terminal:**
```bash
chmod -R 775 backend/storage backend/bootstrap/cache
```

### Step 5: Run Migrations (if terminal available)

```bash
cd backend
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
```

## ✅ Test

1. **Frontend:** http://cpdemo.blueinctech.com
2. **API Health:** http://cpdemo.blueinctech.com/api/health
   - Should return: `{"status":"ok"}`

## 📝 Important Notes

- **Frontend is already built** with correct API URL ✅
- **Backend .env** must be configured
- **File permissions** must be set
- **Database** must be created and configured

## 🆘 Troubleshooting

**If frontend shows but login gives network error:**
- Check API: http://cpdemo.blueinctech.com/api/health
- Verify `backend/.env` is configured
- Check file permissions

**If API returns 500 error:**
- Check `backend/.env` exists and is configured
- Verify database credentials
- Check file permissions on storage folders

Everything is ready! Just compress, upload, extract, and configure!


