# Fix Network Error - API Not Connecting

## The Problem

Frontend is showing but login gives "Network Error" - this means the API is not accessible.

## Quick Checks

### 1. Test API Endpoint

Open in browser: `https://cpdemo.blueinctech.com/api/health`

**Expected:** Should return `{"status":"ok"}`

**If you get 404 or error:**
- API is not accessible at that path
- Check if `api/` folder exists and has `index.php`

**If you get 500 error:**
- Backend configuration issue
- Check `backend/.env` file
- Check file permissions

### 2. Check API URL in Frontend

The frontend was built with an API URL. Check what it's trying to connect to:

**Open browser console (F12) and look for:**
- Network tab → See what URL it's trying to connect to
- Console tab → Look for error messages

**Common issues:**
- Frontend trying to connect to `http://localhost:8000/api` (wrong!)
- Frontend trying to connect to wrong domain
- CORS errors

## Solutions

### Solution 1: Rebuild Frontend with Correct API URL

The frontend needs to be rebuilt with your actual API URL.

**On your local machine:**

```bash
cd frontend
echo "VITE_API_BASE_URL=https://cpdemo.blueinctech.com/api" > .env.production
npm run build
```

Then upload the new `dist/` contents to replace files in your domain root.

### Solution 2: Check API Folder Structure

**Verify in File Manager:**

```
your-domain-root/
├── api/
│   ├── index.php          ← Must exist
│   └── .htaccess          ← Should exist
└── backend/
    └── (all backend files)
```

### Solution 3: Test API Directly

**In browser, try these URLs:**

1. `https://cpdemo.blueinctech.com/api/health`
   - Should return: `{"status":"ok"}`

2. `https://cpdemo.blueinctech.com/api/login`
   - Should return an error (method not allowed for GET) or validation error
   - This confirms API is working

**If these don't work:**
- API path is wrong
- Backend not configured
- Check `api/index.php` paths

### Solution 4: Check api/index.php Paths

**Open `api/index.php` and verify paths:**

Should be:
```php
require __DIR__.'/../backend/vendor/autoload.php';
$app = require_once __DIR__.'/../backend/bootstrap/app.php';
```

**If paths are wrong, fix them!**

### Solution 5: Check CORS Configuration

**In `backend/config/cors.php`, verify:**

```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:5173'),
],
```

**In `backend/.env`, make sure:**
```env
FRONTEND_URL=https://cpdemo.blueinctech.com
```

Then clear cache:
```bash
cd backend
php artisan config:clear
php artisan config:cache
```

### Solution 6: Check Database Connection

**If API returns 500 error, check database:**

1. **Verify `.env` file exists in `backend/` folder**
2. **Check database credentials are correct**
3. **Test database connection:**

```bash
cd backend
php artisan migrate:status
```

If database connection fails, fix `.env` database settings.

## Step-by-Step Debugging

### Step 1: Test API Health Endpoint

Open: `https://cpdemo.blueinctech.com/api/health`

**What you see:**
- ✅ `{"status":"ok"}` → API is working!
- ❌ 404 Not Found → API path wrong or `api/` folder missing
- ❌ 500 Error → Backend configuration issue
- ❌ Network Error → Server/connection issue

### Step 2: Check Browser Console

1. **Open browser DevTools (F12)**
2. **Go to Network tab**
3. **Try to login**
4. **Look at the failed request:**
   - What URL is it trying?
   - What error message?
   - Status code?

### Step 3: Verify File Structure

**In File Manager, check:**

```
your-domain-root/
├── index.html              ← Frontend
├── .htaccess
├── assets/
│
├── api/                    ← API endpoint
│   ├── index.php
│   └── .htaccess
│
└── backend/                ← Backend files
    ├── .env                ← Must exist!
    ├── vendor/
    └── ...
```

### Step 4: Check .env File

**In `backend/.env`, verify:**

```env
APP_URL=https://cpdemo.blueinctech.com/api
FRONTEND_URL=https://cpdemo.blueinctech.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**If .env doesn't exist:**
- Copy `backend/.env.example` to `backend/.env`
- Fill in your values
- Run: `php artisan key:generate`

## Quick Fixes

### Fix 1: Rebuild Frontend (Most Common)

The frontend was built with wrong API URL. Rebuild it:

```bash
# On your local machine
cd frontend
echo "VITE_API_BASE_URL=https://cpdemo.blueinctech.com/api" > .env.production
npm run build
```

Then upload new `dist/` files to replace existing ones.

### Fix 2: Create Missing .env

If `backend/.env` doesn't exist:

1. Copy `backend/.env.example` to `backend/.env`
2. Edit with your database credentials
3. Set `APP_URL` and `FRONTEND_URL`

### Fix 3: Fix API Paths

If `api/index.php` has wrong paths, update it:

```php
require __DIR__.'/../backend/vendor/autoload.php';
$app = require_once __DIR__.'/../backend/bootstrap/app.php';
```

### Fix 4: Set Permissions

```bash
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
chmod 600 backend/.env
```

## Test Checklist

- [ ] API health endpoint works: `https://cpdemo.blueinctech.com/api/health`
- [ ] `backend/.env` file exists and is configured
- [ ] Database credentials in `.env` are correct
- [ ] `api/index.php` has correct paths
- [ ] Frontend API URL matches your domain
- [ ] CORS is configured correctly
- [ ] File permissions are set correctly

## Still Not Working?

1. **Check Apache/PHP error logs** in your hosting control panel
2. **Test API endpoint directly** in browser
3. **Check browser console** for specific error messages
4. **Verify database connection** works
5. **Contact hosting support** if server issues

