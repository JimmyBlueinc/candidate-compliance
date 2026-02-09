# 📝 Manual Steps Guide - Step by Step

This guide covers all manual tasks you need to complete for deployment.

---

## PART 1: Code Fixes (Required Before Deployment)

### Step 1.1: Fix Storage Disk Usage in Controllers

#### File 1: `backend/app/Http/Controllers/Api/AuthController.php`

**Find line 218** (around line 218):
```php
\Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
```

**Replace with**:
```php
\Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->delete($user->avatar_path);
```

**Find line 221** (around line 221):
```php
$path = $file->store('avatars', 'public');
```

**Replace with**:
```php
$path = $file->store('avatars', config('filesystems.default'));
```

---

#### File 2: `backend/app/Http/Controllers/Api/CredentialController.php`

**Find line 145** (around line 145):
```php
$path = $request->file('document')->store('credentials', 'public');
```

**Replace with**:
```php
$path = $request->file('document')->store('credentials', config('filesystems.default'));
```

**Find any other instances** of `->store(..., 'public')` in this file and replace with:
```php
->store(..., config('filesystems.default'))
```

---

#### File 3: `backend/app/Http/Controllers/Api/BackgroundCheckController.php`

**Find line 141** (around line 141):
```php
Storage::disk('public')->delete($backgroundCheck->document_path);
```

**Replace with**:
```php
Storage::disk(config('filesystems.default'))->delete($backgroundCheck->document_path);
```

**Find line 179** (around line 179) - same change:
```php
Storage::disk('public')->delete($backgroundCheck->document_path);
```

**Replace with**:
```php
Storage::disk(config('filesystems.default'))->delete($backgroundCheck->document_path);
```

**Also find** any `->store(..., 'public')` and replace with `->store(..., config('filesystems.default'))`

---

#### File 4: `backend/app/Http/Controllers/Api/HealthRecordController.php`

**Find line 145** (around line 145):
```php
Storage::disk('public')->delete($healthRecord->document_path);
```

**Replace with**:
```php
Storage::disk(config('filesystems.default'))->delete($healthRecord->document_path);
```

**Find line 172** (around line 172) - same change:
```php
Storage::disk('public')->delete($healthRecord->document_path);
```

**Replace with**:
```php
Storage::disk(config('filesystems.default'))->delete($healthRecord->document_path);
```

**Also find** any `->store(..., 'public')` and replace with `->store(..., config('filesystems.default'))`

---

#### File 5: `backend/app/Http/Controllers/Api/WorkAuthorizationController.php`

**Find line 143** (around line 143):
```php
Storage::disk('public')->delete($workAuthorization->document_path);
```

**Replace with**:
```php
Storage::disk(config('filesystems.default'))->delete($workAuthorization->document_path);
```

**Find line 181** (around line 181) - same change:
```php
Storage::disk('public')->delete($workAuthorization->document_path);
```

**Replace with**:
```php
Storage::disk(config('filesystems.default'))->delete($workAuthorization->document_path);
```

**Also find** any `->store(..., 'public')` and replace with `->store(..., config('filesystems.default'))`

---

### Step 1.2: Fix Model URL Accessors

#### File 1: `backend/app/Models/User.php`

**Find the `getAvatarUrlAttribute()` method** (around lines 55-93).

**Replace the ENTIRE method** with:
```php
public function getAvatarUrlAttribute(): ?string
{
    if (!$this->avatar_path) {
        return null;
    }
    
    // Use Storage::url() which handles S3 automatically
    $url = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->avatar_path);
    
    // Add cache-busting parameter using updated_at timestamp
    if ($this->updated_at) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'v=' . $this->updated_at->timestamp;
    }
    
    return $url;
}
```

---

#### File 2: `backend/app/Models/Credential.php`

**Find the `getDocumentUrlAttribute()` method** (around line 108).

**Replace the ENTIRE method** with:
```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

---

#### File 3: `backend/app/Models/BackgroundCheck.php`

**Find the `getDocumentUrlAttribute()` method** (around line 53).

**Replace the ENTIRE method** with:
```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

---

#### File 4: `backend/app/Models/HealthRecord.php`

**Find the `getDocumentUrlAttribute()` method** (around line 48).

**Replace the ENTIRE method** with:
```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

---

#### File 5: `backend/app/Models/WorkAuthorization.php`

**Find the `getDocumentUrlAttribute()` method** (around line 54).

**Replace the ENTIRE method** with:
```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

---

#### File 6: `backend/app/Models/TrainingRecord.php`

**Find the `getDocumentUrlAttribute()` method** (around line 49).

**Replace the ENTIRE method** with:
```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

---

#### File 7: `backend/app/Models/PerformanceRecord.php`

**Find the `getDocumentUrlAttribute()` method** (around line 50).

**Replace the ENTIRE method** with:
```php
public function getDocumentUrlAttribute(): ?string
{
    if (!$this->document_path) {
        return null;
    }
    
    return \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->document_path);
}
```

---

### Step 1.3: Commit Code Changes

1. **Open terminal/command prompt**
2. **Navigate to project directory**:
   ```bash
   cd C:\Users\MIKENZY\Documents\Apps\goodwill
   ```

3. **Check what files changed**:
   ```bash
   git status
   ```

4. **Add all changes**:
   ```bash
   git add .
   ```

5. **Commit changes**:
   ```bash
   git commit -m "Fix storage for S3 compatibility - Render deployment"
   ```

6. **Push to GitHub**:
   ```bash
   git push origin main
   ```

---

## PART 2: Set Up S3 Storage (Choose One)

### Option A: AWS S3 (Free Tier - 5GB for 12 months)

1. **Go to**: https://aws.amazon.com/s3/
2. **Sign up** for AWS account (if you don't have one)
3. **Go to S3 Console**: https://console.aws.amazon.com/s3/
4. **Click "Create bucket"**
5. **Configure bucket**:
   - **Bucket name**: `goodwill-storage` (must be globally unique)
   - **Region**: Choose closest to you (e.g., `us-east-1`)
   - **Block Public Access**: Uncheck "Block all public access" (we need public read for files)
   - **Bucket Versioning**: Disable
   - **Default encryption**: Enable (optional)
   - Click "Create bucket"

6. **Create IAM User for Access**:
   - Go to: https://console.aws.amazon.com/iam/
   - Click "Users" → "Create user"
   - **User name**: `goodwill-s3-user`
   - **Access type**: "Programmatic access"
   - Click "Next: Permissions"

7. **Attach Policy**:
   - Click "Attach existing policies directly"
   - Search for "S3" and select: `AmazonS3FullAccess` (or create custom policy with read/write to your bucket)
   - Click "Next: Tags" → "Next: Review" → "Create user"

8. **Save Credentials**:
   - **Access Key ID**: Copy this (you'll need it)
   - **Secret Access Key**: Copy this NOW (you can't see it again)
   - Save these securely

9. **Configure Bucket CORS** (for browser access):
   - Go to your bucket → "Permissions" tab
   - Scroll to "Cross-origin resource sharing (CORS)"
   - Click "Edit" and paste:
   ```json
   [
       {
           "AllowedHeaders": ["*"],
           "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
           "AllowedOrigins": ["*"],
           "ExposeHeaders": []
       }
   ]
   ```
   - Click "Save changes"

10. **Your S3 Details** (write these down):
    - **Access Key ID**: `AKIA...`
    - **Secret Access Key**: `xxxxx`
    - **Region**: `us-east-1` (or your chosen region)
    - **Bucket Name**: `goodwill-storage`
    - **Endpoint**: `https://s3.amazonaws.com` (or `https://s3.us-east-1.amazonaws.com`)

---

### Option B: DigitalOcean Spaces ($5/month, 250GB)

1. **Go to**: https://www.digitalocean.com/products/spaces
2. **Sign up** for DigitalOcean account
3. **Go to Spaces**: https://cloud.digitalocean.com/spaces
4. **Click "Create a Space"**
5. **Configure**:
   - **Name**: `goodwill-storage` (must be unique)
   - **Region**: Choose closest
   - **File listing**: Private (or Public if you want)
   - Click "Create a Space"

6. **Create API Key**:
   - Go to: https://cloud.digitalocean.com/account/api/spaces
   - Click "Generate New Key"
   - **Name**: `goodwill-spaces-key`
   - **Access**: "Read and Write"
   - Click "Generate Key"
   - **Save Access Key and Secret Key**

7. **Your Spaces Details** (write these down):
    - **Access Key**: `xxxxx`
    - **Secret Key**: `xxxxx`
    - **Region**: `nyc3` (or your chosen region)
    - **Bucket Name**: `goodwill-storage`
    - **Endpoint**: `https://nyc3.digitaloceanspaces.com` (replace `nyc3` with your region)

---

### Option C: Backblaze B2 (Free Tier - 10GB)

1. **Go to**: https://www.backblaze.com/b2/cloud-storage.html
2. **Sign up** for Backblaze account
3. **Go to B2 Cloud Storage**: https://secure.backblaze.com/user_signin.htm
4. **Create Bucket**:
   - Click "Buckets" → "Create a Bucket"
   - **Bucket Name**: `goodwill-storage`
   - **Bucket Type**: Public (or Private with public file URLs)
   - Click "Create a Bucket"

5. **Create Application Key**:
   - Click "App Keys" → "Add a New Application Key"
   - **Name**: `goodwill-b2-key`
   - **Allow List**: All (or specific bucket)
   - **Allow Read**: All
   - **Allow Write**: All
   - **Allow Delete**: All
   - Click "Create New Key"
   - **Save Key ID and Application Key**

6. **Your B2 Details** (write these down):
    - **Key ID**: `xxxxx`
    - **Application Key**: `xxxxx`
    - **Bucket Name**: `goodwill-storage`
    - **Endpoint**: `https://s3.us-west-000.backblazeb2.com` (check your B2 dashboard for exact endpoint)

---

## PART 3: Set Up SMTP Email (Choose One)

### Option A: SendGrid (Free Tier - 100 emails/day)

1. **Go to**: https://sendgrid.com/
2. **Sign up** for free account
3. **Verify your email** (check inbox)
4. **Go to Settings** → **API Keys**: https://app.sendgrid.com/settings/api_keys
5. **Click "Create API Key"**
6. **Configure**:
   - **Name**: `goodwill-api-key`
   - **Permissions**: "Full Access" (or "Restricted Access" with Mail Send)
   - Click "Create & View"
   - **Copy API Key** (you can't see it again!)

7. **Verify Sender** (required):
   - Go to: https://app.sendgrid.com/settings/sender_auth/senders/new
   - **From Email**: `noreply@yourdomain.com` (or use a real email)
   - **From Name**: `Goodwill Staffing`
   - Fill in other details
   - **Verify email** (check inbox and click verification link)

8. **Your SendGrid Details** (write these down):
    - **SMTP Host**: `smtp.sendgrid.net`
    - **SMTP Port**: `587`
    - **SMTP Username**: `apikey`
    - **SMTP Password**: `SG.xxxxx` (your API key)
    - **From Email**: `noreply@yourdomain.com`
    - **From Name**: `Goodwill Staffing`

---

### Option B: Mailgun (Free Tier - 5,000 emails/month)

1. **Go to**: https://www.mailgun.com/
2. **Sign up** for free account
3. **Verify your email** (check inbox)
4. **Add Domain** (or use sandbox domain for testing):
   - Go to: https://app.mailgun.com/app/sending/domains
   - For testing: Use sandbox domain (e.g., `sandbox123.mailgun.org`)
   - For production: Add your own domain (requires DNS setup)

5. **Get API Key**:
   - Go to: https://app.mailgun.com/app/account/security/api_keys
   - Copy "Private API key"

6. **Your Mailgun Details** (write these down):
    - **SMTP Host**: `smtp.mailgun.org`
    - **SMTP Port**: `587`
    - **SMTP Username**: `postmaster@your-domain.mailgun.org`
    - **SMTP Password**: `xxxxx` (your Private API key)
    - **From Email**: `noreply@your-domain.mailgun.org`
    - **From Name**: `Goodwill Staffing`

---

## PART 4: Deploy Backend to Render

### Step 4.1: Create PostgreSQL Database

1. **Go to**: https://dashboard.render.com
2. **Sign up/Login** (use GitHub account for easy integration)
3. **Click "New +"** (top right)
4. **Select "PostgreSQL"**
5. **Configure**:
   - **Name**: `goodwill-db`
   - **Database**: `goodwill` (auto-filled)
   - **User**: `goodwill_user` (auto-filled)
   - **Region**: Choose closest to you
   - **PostgreSQL Version**: Latest (14 or 15)
   - **Plan**: **Free**
   - Click "Create Database"

6. **Wait for database to be created** (1-2 minutes)

7. **Copy Database Credentials**:
   - Click on your database
   - **Copy these values** (you'll need them):
     - **Internal Database URL**: `postgresql://goodwill_user:xxxxx@dpg-xxxxx-a/goodwill_xxxxx`
     - **Host**: `dpg-xxxxx-a.render.com`
     - **Port**: `5432`
     - **Database**: `goodwill_xxxxx`
     - **User**: `goodwill_user`
     - **Password**: `xxxxx` (click "Show" to reveal)

---

### Step 4.2: Create Web Service (Backend)

1. **In Render Dashboard**, click **"New +"** → **"Web Service"**

2. **Connect GitHub**:
   - Click "Connect account" if not connected
   - Authorize Render to access your GitHub
   - Select repository: `goodwillstaffing` (or your repo name)
   - Click "Connect"

3. **Configure Service**:
   - **Name**: `goodwill-backend`
   - **Region**: Same as database (or closest)
   - **Branch**: `main`
   - **Root Directory**: `backend` ⚠️ **IMPORTANT**
   - **Runtime**: `Docker`
   - **Dockerfile Path**: `backend/Dockerfile` ⚠️ **IMPORTANT**
   - **Docker Context**: `backend` ⚠️ **IMPORTANT**
   - **Plan**: **Free**

4. **Click "Create Web Service"**

5. **Wait for initial build** (5-10 minutes)

---

### Step 4.3: Set Environment Variables (Backend)

1. **In your Web Service**, go to **"Environment"** tab

2. **Add these variables one by one** (click "Add Environment Variable" for each):

#### Application Variables:
```
APP_NAME = Goodwill Staffing Compliance Tracker
APP_ENV = production
APP_DEBUG = false
APP_KEY = (generate this - see Step 4.4)
APP_URL = https://goodwill-backend.onrender.com (use YOUR service URL)
LOG_LEVEL = error
```

#### Frontend/CORS Variables:
```
FRONTEND_URL = https://your-app.vercel.app (you'll update this after frontend deploys)
VERCEL_URL = https://your-app.vercel.app (you'll update this after frontend deploys)
```

#### Database Variables (from Step 4.1):
```
DB_CONNECTION = pgsql
DB_HOST = dpg-xxxxx-a.render.com (from your database)
DB_PORT = 5432
DB_DATABASE = goodwill_xxxxx (from your database)
DB_USERNAME = goodwill_user
DB_PASSWORD = xxxxx (from your database)
```

#### Storage/S3 Variables (from Part 2):
```
FILESYSTEM_DISK = s3
AWS_ACCESS_KEY_ID = xxxxx (from S3 setup)
AWS_SECRET_ACCESS_KEY = xxxxx (from S3 setup)
AWS_DEFAULT_REGION = us-east-1 (or your region)
AWS_BUCKET = goodwill-storage (or your bucket name)
AWS_ENDPOINT = https://s3.amazonaws.com (or your S3-compatible endpoint)
AWS_USE_PATH_STYLE_ENDPOINT = false (or true for S3-compatible like B2)
```

#### Mail/SMTP Variables (from Part 3):
```
MAIL_MAILER = smtp
MAIL_HOST = smtp.sendgrid.net (or your SMTP host)
MAIL_PORT = 587
MAIL_USERNAME = apikey (or your SMTP username)
MAIL_PASSWORD = SG.xxxxx (or your SMTP password)
MAIL_ENCRYPTION = tls
MAIL_FROM_ADDRESS = noreply@yourdomain.com
MAIL_FROM_NAME = Goodwill Staffing
```

#### Super Admin Variable:
```
SUPER_ADMIN_SECRET_KEY = (generate a random string - see Step 4.5)
```

3. **Click "Save Changes"** after adding all variables

---

### Step 4.4: Generate APP_KEY

1. **Open terminal/command prompt**

2. **Navigate to backend directory**:
   ```bash
   cd C:\Users\MIKENZY\Documents\Apps\goodwill\backend
   ```

3. **Generate key**:
   ```bash
   php artisan key:generate --show
   ```

4. **Copy the output** (looks like `base64:xxxxx...`)

5. **Go back to Render** → Environment tab

6. **Update `APP_KEY`** with the generated value

7. **Click "Save Changes"**

---

### Step 4.5: Generate SUPER_ADMIN_SECRET_KEY

1. **Open terminal/command prompt**

2. **Generate random string** (Windows PowerShell):
   ```powershell
   -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | ForEach-Object {[char]$_})
   ```

   Or use online generator: https://www.random.org/strings/

3. **Copy the generated string** (32+ characters)

4. **In Render** → Environment tab

5. **Set `SUPER_ADMIN_SECRET_KEY`** to this value

6. **Click "Save Changes"**

---

### Step 4.6: Configure Health Check

1. **In your Web Service**, go to **"Settings"** tab

2. **Scroll to "Health Check Path"**

3. **Enter**: `/api/health`

4. **Click "Save Changes"**

---

### Step 4.7: Deploy Backend

1. **Go to "Events" tab** in your Web Service

2. **Click "Manual Deploy"** → **"Deploy latest commit"**

3. **Wait for deployment** (5-10 minutes)

4. **Check logs** for any errors:
   - Go to "Logs" tab
   - Look for "Build successful" or errors

5. **Test health check**:
   - Copy your service URL (e.g., `https://goodwill-backend.onrender.com`)
   - Visit: `https://goodwill-backend.onrender.com/api/health`
   - Should see: `{"status":"ok","timestamp":"...","environment":"production"}`

6. **Copy your backend URL** (you'll need it for frontend)

---

## PART 5: Deploy Frontend to Vercel

### Step 5.1: Create Vercel Account

1. **Go to**: https://vercel.com
2. **Sign up** (use GitHub account for easy integration)
3. **Authorize** Vercel to access your GitHub

---

### Step 5.2: Import Project

1. **In Vercel Dashboard**, click **"Add New..."** → **"Project"**

2. **Import Git Repository**:
   - Find your repository: `goodwillstaffing` (or your repo name)
   - Click **"Import"**

3. **Configure Project**:
   - **Project Name**: `goodwill-frontend` (or any name)
   - **Framework Preset**: **Vite** (should auto-detect)
   - **Root Directory**: `frontend` ⚠️ **IMPORTANT** - Click "Edit" and set to `frontend`
   - **Build Command**: `npm run build` (should be auto-filled)
   - **Output Directory**: `dist` (should be auto-filled)
   - **Install Command**: `npm install` (should be auto-filled)

4. **Click "Deploy"** (don't add environment variables yet)

5. **Wait for first deployment** (2-5 minutes)

---

### Step 5.3: Set Environment Variable

1. **After deployment**, go to your project

2. **Click "Settings"** → **"Environment Variables"**

3. **Add Variable**:
   - **Key**: `VITE_API_BASE_URL`
   - **Value**: `https://goodwill-backend.onrender.com/api` (use YOUR backend URL from Part 4)
   - **Environment**: Select all (Production, Preview, Development)
   - Click **"Save"**

4. **Redeploy**:
   - Go to **"Deployments"** tab
   - Click **"..."** on latest deployment → **"Redeploy"**
   - Or push a new commit to trigger redeploy

5. **Wait for redeployment** (2-5 minutes)

6. **Copy your frontend URL** (e.g., `https://goodwill-app.vercel.app`)

---

### Step 5.4: Update Backend CORS

1. **Go back to Render** → Your Backend Service

2. **Go to "Environment"** tab

3. **Update these variables**:
   - `FRONTEND_URL` = `https://your-app.vercel.app` (use YOUR Vercel URL)
   - `VERCEL_URL` = `https://your-app.vercel.app` (use YOUR Vercel URL)

4. **Click "Save Changes"**

5. **Redeploy backend** (or wait for auto-deploy):
   - Go to "Events" tab
   - Click "Manual Deploy" → "Deploy latest commit"

---

## PART 6: Configure Cron Job (Scheduler)

1. **In Render Dashboard**, click **"New +"** → **"Cron Job"**

2. **Configure**:
   - **Name**: `goodwill-scheduler`
   - **Schedule**: `* * * * *` (every minute)
   - **Command**: `cd backend && php artisan schedule:run`
   - **Service**: Select your `goodwill-backend` service
   - **Plan**: **Free**

3. **Click "Create Cron Job"**

4. **Note**: This is best-effort. If service is sleeping, cron may not run.

---

## PART 7: Testing

### Step 7.1: Test Frontend

1. **Visit your Vercel URL**: `https://your-app.vercel.app`

2. **Check if it loads** (should see login page)

3. **Open browser console** (F12) and check for errors

---

### Step 7.2: Test Backend

1. **Test health check**:
   - Visit: `https://your-backend.onrender.com/api/health`
   - Should see: `{"status":"ok",...}`

2. **Test API** (using browser or Postman):
   - Visit: `https://your-backend.onrender.com/api/register`
   - Should see validation error (expected - means API is working)

---

### Step 7.3: Test Registration

1. **Go to frontend**: `https://your-app.vercel.app`

2. **Click "Register"** (or "Sign Up")

3. **Fill in form**:
   - Name: `Test User`
   - Email: `test@example.com`
   - Password: `password123`
   - Confirm Password: `password123`
   - Role: `admin` (or `recruiter`)

4. **Submit**

5. **Check if registration works** (should redirect to dashboard or show success)

---

### Step 7.4: Test Login

1. **Use credentials from registration**

2. **Login**

3. **Check if you're logged in** (should see dashboard)

---

### Step 7.5: Test File Upload

1. **After logging in**, go to **Settings/Profile**

2. **Upload an avatar image**

3. **Check**:
   - Does upload complete?
   - Does image appear?
   - Check browser console for errors
   - Check S3 bucket - file should be there

---

### Step 7.6: Test Credential Creation

1. **Go to Dashboard**

2. **Click "Add Credential"** (or similar button)

3. **Fill in form**:
   - Candidate Name: `John Doe`
   - Position: `Developer`
   - Credential Type: `License`
   - Issue Date: Today
   - Expiry Date: Future date
   - Email: `john@example.com`

4. **Upload a document** (optional)

5. **Submit**

6. **Check**:
   - Credential appears in list?
   - Document uploaded to S3?
   - Document URL works?

---

### Step 7.7: Test Email (Optional)

1. **Use Mailtrap for testing first** (safer):
   - Sign up: https://mailtrap.io
   - Get SMTP credentials
   - Update Render environment variables temporarily
   - Test email sending
   - Check Mailtrap inbox

2. **Or test with real SMTP**:
   - Check your email inbox for test emails
   - Check spam folder

---

## PART 8: Create First Super Admin

1. **Go to frontend**: `https://your-app.vercel.app`

2. **Navigate to**: `https://your-app.vercel.app/create-super-admin`

3. **Enter**:
   - **Secret Key**: The `SUPER_ADMIN_SECRET_KEY` you set in Render
   - **Name**: Your name
   - **Email**: Your email
   - **Password**: Strong password
   - **Confirm Password**: Same password

4. **Submit**

5. **Check if super admin is created**

6. **Login with super admin credentials**

---

## ✅ Final Checklist

- [ ] Code fixes applied (Part 1)
- [ ] Code committed and pushed to GitHub
- [ ] S3 storage set up (Part 2)
- [ ] SMTP email set up (Part 3)
- [ ] Backend deployed to Render (Part 4)
- [ ] All environment variables set in Render
- [ ] Frontend deployed to Vercel (Part 5)
- [ ] Environment variable set in Vercel
- [ ] CORS updated in backend
- [ ] Cron job configured (Part 6)
- [ ] All tests pass (Part 7)
- [ ] Super admin created (Part 8)

---

## 🆘 If Something Fails

### Backend Won't Start
1. **Check Render Logs**: Dashboard → Service → Logs
2. **Check environment variables** are all set
3. **Check database connection** (verify credentials)
4. **Check Docker build** logs for errors

### Frontend Can't Connect
1. **Check `VITE_API_BASE_URL`** is correct
2. **Check backend is running** (test health endpoint)
3. **Check browser console** for CORS errors
4. **Verify CORS** in backend environment variables

### File Upload Fails
1. **Check S3 credentials** in Render
2. **Check bucket exists** and is accessible
3. **Check bucket permissions** (public read)
4. **Check code fixes** were applied correctly

### Email Not Sending
1. **Check SMTP credentials** in Render
2. **Test with Mailtrap first** (easier to debug)
3. **Check email logs** in Render
4. **Verify sender email** is verified (SendGrid/Mailgun)

---

**You're Done!** 🎉

Your application should now be live at:
- **Frontend**: `https://your-app.vercel.app`
- **Backend**: `https://your-backend.onrender.com/api`

