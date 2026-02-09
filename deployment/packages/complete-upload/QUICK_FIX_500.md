# Quick Fix for 500 Error

## Most Likely Issues (in order of probability):

### 1. Missing .env File ⚠️ MOST COMMON
**Check:** Does `backend/.env` exist?

**Fix:**
1. Copy `backend/.env.example` to `backend/.env`
2. Edit `backend/.env` and set:
   - `APP_KEY` (see below)
   - Database credentials
   - `APP_URL=http://cpdemo.blueinctech.com`

### 2. Missing APP_KEY
**Fix:**
- If you have SSH: `cd backend && php artisan key:generate`
- If not: Generate key and add to `.env`:
  ```
  APP_KEY=base64:YOUR_32_CHAR_KEY_HERE
  ```

### 3. Database Not Configured
**Fix:**
Edit `backend/.env`:
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 4. Storage Permissions
**Fix:**
Set permissions on:
- `backend/storage` → 775 or 777
- `backend/bootstrap/cache` → 775 or 777

## Diagnostic Tool

Visit: **http://cpdemo.blueinctech.com/api/test.php**

This will show you exactly what's wrong!

## Check Logs

View: `backend/storage/logs/laravel.log`

The last error in this file tells you exactly what's wrong.




