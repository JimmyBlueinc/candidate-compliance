# Goodwill Staffing - Ready to Upload Package

## ✅ Everything is Ready!

This folder contains everything you need. Just:
1. Compress this entire folder to ZIP
2. Upload to your domain root
3. Extract it
4. Configure backend/.env file
5. Rebuild frontend with correct API URL

## 📦 What's Inside

```
goodwill-ready-to-upload/
│
├── index.html              ← Frontend homepage
├── .htaccess               ← Frontend routing
├── assets/                 ← Frontend CSS/JS
├── vite.svg
│
├── api/                    ← Backend API endpoint
│   ├── index.php           ← Already configured correctly
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

## 🚀 Quick Setup

### Step 1: Compress and Upload

1. **Select this entire folder** (`goodwill-ready-to-upload`)
2. **Right-click → Compress to ZIP** (or use 7-Zip/WinRAR)
3. **Name it:** `goodwill-app.zip`
4. **Upload to your domain root** via File Manager
5. **Extract** the ZIP file

### Step 2: Configure Backend

**Option A: Use setup.php (Easiest)**
1. Upload `setup.php` to domain root
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
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   SESSION_SECURE_COOKIE=false
   SANCTUM_STATEFUL_DOMAINS=cpdemo.blueinctech.com
   ```

### Step 3: Rebuild Frontend (IMPORTANT!)

The frontend needs to be rebuilt with your API URL:

**On your local machine:**
```bash
cd frontend
echo "VITE_API_BASE_URL=http://cpdemo.blueinctech.com/api" > .env.production
npm run build
```

Then upload ALL files from `frontend/dist/` to replace files in domain root.

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

- **Frontend must be rebuilt** with correct API URL
- **Backend .env** must be configured
- **File permissions** must be set
- **Database** must be created and configured

## 🆘 Need Help?

See other files in this folder:
- `REBUILD_FRONTEND.txt` - How to rebuild frontend
- `CONFIGURE_ENV.txt` - How to configure backend
- `FIX_NETWORK_ERROR.txt` - Troubleshooting

Everything is ready! Just compress, upload, extract, and configure!

