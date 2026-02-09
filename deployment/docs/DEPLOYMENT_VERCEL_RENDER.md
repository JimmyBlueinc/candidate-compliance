# 🚀 Deployment Guide: Vercel (Frontend) + Render (Backend)
## Goodwill Staffing Compliance Tracker

**Deployment Strategy:**
- **Frontend**: Vercel (Free Tier) - React 19 + Vite SPA
- **Backend**: Render (Free Web Service) - Laravel 12 API
- **Database**: Render PostgreSQL (Free Tier)
- **Storage**: S3-compatible storage (no local filesystem)
- **Scheduler**: Render Cron Job (Free Tier)

---

## ⚠️ CRITICAL CONSTRAINTS & FIXES

### Issues Identified & Solutions

#### 1. **Storage Disk Usage** ❌ → ✅
**Problem**: Code uses `Storage::disk('public')` explicitly, which won't work with S3.

**Files Affected**:
- `backend/app/Http/Controllers/Api/AuthController.php`
- `backend/app/Http/Controllers/Api/CredentialController.php`
- `backend/app/Http/Controllers/Api/BackgroundCheckController.php`
- `backend/app/Http/Controllers/Api/HealthRecordController.php`
- `backend/app/Http/Controllers/Api/WorkAuthorizationController.php`
- All Model files with `document_url` accessors

**Fix**: Use `Storage::disk()` without 'public' or use default disk:
```php
// OLD (won't work with S3):
Storage::disk('public')->store('avatars', 'public');

// NEW (works with S3):
Storage::disk(config('filesystems.default'))->put('avatars', $file);
// OR
Storage::put('avatars', $file); // Uses default disk
```

**Action Required**: Update all `Storage::disk('public')` calls to use default disk.

#### 2. **Avatar URL Generation** ❌ → ✅
**Problem**: `User::getAvatarUrlAttribute()` constructs URLs manually, won't work with S3.

**Fix**: Use `Storage::url()` for S3 URLs:
```php
public function getAvatarUrlAttribute(): ?string
{
    if (!$this->avatar_path) {
        return null;
    }
    
    // Use Storage::url() which handles S3 automatically
    $url = Storage::disk(config('filesystems.default'))->url($this->avatar_path);
    
    // Add cache-busting
    if ($this->updated_at) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'v=' . $this->updated_at->timestamp;
    }
    return $url;
}
```

**Action Required**: Update `User` model and all models with `document_url` accessors.

#### 3. **Storage Symlink** ❌ → ✅
**Problem**: `php artisan storage:link` won't work (no persistent filesystem).

**Fix**: Remove from deployment scripts. S3 doesn't need symlinks.

**Action Required**: Remove `storage:link` from any deployment scripts.

#### 4. **Scheduler Sleep Handling** ⚠️ → ✅
**Problem**: Render free services sleep after 15 minutes of inactivity. Cron jobs may not run.

**Fix**: 
- Use Render Cron Job (best-effort, but better than nothing)
- Add health check endpoint to prevent sleep (Render pings every 5 minutes)
- Accept that reminders may be delayed (document this limitation)

**Action Required**: Add health check endpoint, configure Render health check URL.

#### 5. **CORS Configuration** ✅
**Status**: Already configured, but needs Vercel URL.

**Action Required**: Add Vercel URL to `FRONTEND_URL` environment variable.

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### Code Changes Required

- [ ] Update all `Storage::disk('public')` to use default disk
- [ ] Update `User::getAvatarUrlAttribute()` to use `Storage::url()`
- [ ] Update all model `document_url` accessors to use `Storage::url()`
- [ ] Remove `storage:link` from deployment scripts
- [ ] Add health check endpoint for Render
- [ ] Update CORS to accept Vercel domain

### Environment Setup

- [ ] Create S3-compatible storage account (AWS S3, DigitalOcean Spaces, Backblaze B2, etc.)
- [ ] Get S3 credentials (Access Key, Secret Key, Bucket, Region, Endpoint)
- [ ] Get SMTP credentials (SendGrid, Mailgun, or similar)
- [ ] Prepare database credentials (Render will provide)

---

## 🔧 STEP-BY-STEP DEPLOYMENT

### Step 1: Prepare GitHub Repository

1. **Ensure repository structure**:
   ```
   goodwillstaffing/
   ├── frontend/          # React app
   ├── backend/           # Laravel app
   └── README.md
   ```

2. **Commit all changes** (including Dockerfile, config updates):
   ```bash
   git add .
   git commit -m "Prepare for Vercel + Render deployment"
   git push origin main
   ```

### Step 2: Deploy Backend to Render

1. **Go to Render Dashboard**: https://dashboard.render.com
2. **Click "New +" → "Web Service"**
3. **Connect GitHub repository**
4. **Configure service**:
   - **Name**: `goodwill-backend`
   - **Environment**: `Docker`
   - **Region**: Choose closest to your users
   - **Branch**: `main`
   - **Root Directory**: `backend`
   - **Dockerfile Path**: `backend/Dockerfile`
   - **Docker Context**: `backend`
   - **Plan**: `Free`

5. **Set Environment Variables** (see Environment Variables section below)

6. **Add PostgreSQL Database**:
   - Click "New +" → "PostgreSQL"
   - Name: `goodwill-db`
   - Plan: `Free`
   - Copy database credentials

7. **Configure Health Check**:
   - **Health Check Path**: `/api/health`
   - **Health Check Interval**: `5 minutes`

8. **Deploy**: Click "Create Web Service"

9. **Copy Service URL**: `https://goodwill-backend.onrender.com`

### Step 3: Deploy Frontend to Vercel

1. **Go to Vercel Dashboard**: https://vercel.com
2. **Click "Add New..." → "Project"**
3. **Import GitHub repository**
4. **Configure project**:
   - **Framework Preset**: `Vite`
   - **Root Directory**: `frontend`
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`
   - **Install Command**: `npm install`

5. **Set Environment Variables**:
   - `VITE_API_BASE_URL`: `https://goodwill-backend.onrender.com/api`

6. **Deploy**: Click "Deploy"

7. **Copy Frontend URL**: `https://goodwill-app.vercel.app`

### Step 4: Configure Render Cron Job

1. **In Render Dashboard**: Click "New +" → "Cron Job"
2. **Configure**:
   - **Name**: `goodwill-scheduler`
   - **Schedule**: `* * * * *` (every minute)
   - **Command**: `cd backend && php artisan schedule:run`
   - **Service**: Select your backend service
   - **Plan**: `Free`

3. **Note**: This is best-effort. Service may sleep, causing missed runs.

### Step 5: Update Backend Environment Variables

1. **In Render Dashboard** → Your Backend Service → Environment
2. **Add/Update**:
   - `FRONTEND_URL`: `https://goodwill-app.vercel.app`
   - `VERCEL_URL`: `https://goodwill-app.vercel.app` (for CORS)

### Step 6: Test Deployment

1. **Test Frontend**: Visit Vercel URL
2. **Test Backend Health**: `https://goodwill-backend.onrender.com/api/health`
3. **Test Login**: Try logging in from frontend
4. **Test File Upload**: Upload avatar/document (should use S3)
5. **Test API**: Use Postman/curl to test endpoints

---

## 🔐 ENVIRONMENT VARIABLES

### Vercel (Frontend)

| Variable | Value | Required |
|----------|-------|----------|
| `VITE_API_BASE_URL` | `https://goodwill-backend.onrender.com/api` | ✅ Yes |

**How to Set**:
1. Vercel Dashboard → Your Project → Settings → Environment Variables
2. Add variable
3. Redeploy (or it will auto-deploy)

### Render (Backend)

#### Application
| Variable | Value | Required |
|----------|-------|----------|
| `APP_NAME` | `Goodwill Staffing Compliance Tracker` | ✅ Yes |
| `APP_ENV` | `production` | ✅ Yes |
| `APP_DEBUG` | `false` | ✅ Yes |
| `APP_KEY` | `base64:...` (generate with `php artisan key:generate`) | ✅ Yes |
| `APP_URL` | `https://goodwill-backend.onrender.com` | ✅ Yes |
| `LOG_LEVEL` | `error` | ⚪ Optional |

#### Frontend/CORS
| Variable | Value | Required |
|----------|-------|----------|
| `FRONTEND_URL` | `https://goodwill-app.vercel.app` | ✅ Yes |
| `VERCEL_URL` | `https://goodwill-app.vercel.app` | ✅ Yes |

#### Database (Render PostgreSQL)
| Variable | Value | Required |
|----------|-------|----------|
| `DB_CONNECTION` | `pgsql` | ✅ Yes |
| `DB_HOST` | `dpg-xxxxx-a.render.com` (from Render) | ✅ Yes |
| `DB_PORT` | `5432` | ✅ Yes |
| `DB_DATABASE` | `goodwill_xxxxx` (from Render) | ✅ Yes |
| `DB_USERNAME` | `goodwill_user` (from Render) | ✅ Yes |
| `DB_PASSWORD` | `xxxxx` (from Render) | ✅ Yes |

#### Storage (S3-Compatible)
| Variable | Value | Required |
|----------|-------|----------|
| `FILESYSTEM_DISK` | `s3` | ✅ Yes |
| `AWS_ACCESS_KEY_ID` | `your-access-key` | ✅ Yes |
| `AWS_SECRET_ACCESS_KEY` | `your-secret-key` | ✅ Yes |
| `AWS_DEFAULT_REGION` | `us-east-1` (or your region) | ✅ Yes |
| `AWS_BUCKET` | `goodwill-storage` | ✅ Yes |
| `AWS_ENDPOINT` | `https://s3.amazonaws.com` (or your S3-compatible endpoint) | ✅ Yes |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` (or `true` for S3-compatible) | ⚪ Optional |
| `AWS_URL` | `https://your-bucket.s3.amazonaws.com` | ⚪ Optional |

**S3-Compatible Options** (Free/Cheap):
- **AWS S3**: Free tier (5GB, 12 months)
- **DigitalOcean Spaces**: $5/month (250GB)
- **Backblaze B2**: Free tier (10GB)
- **Cloudflare R2**: Free tier (10GB)

#### Mail (SMTP)
| Variable | Value | Required |
|----------|-------|----------|
| `MAIL_MAILER` | `smtp` | ✅ Yes |
| `MAIL_HOST` | `smtp.sendgrid.net` (or your SMTP) | ✅ Yes |
| `MAIL_PORT` | `587` | ✅ Yes |
| `MAIL_USERNAME` | `apikey` (or your username) | ✅ Yes |
| `MAIL_PASSWORD` | `your-api-key` | ✅ Yes |
| `MAIL_ENCRYPTION` | `tls` | ✅ Yes |
| `MAIL_FROM_ADDRESS` | `noreply@yourdomain.com` | ✅ Yes |
| `MAIL_FROM_NAME` | `Goodwill Staffing` | ✅ Yes |

**SMTP Options** (Free/Cheap):
- **SendGrid**: Free tier (100 emails/day)
- **Mailgun**: Free tier (5,000 emails/month)
- **Mailtrap**: Free tier (testing only)

#### Sanctum
| Variable | Value | Required |
|----------|-------|----------|
| `SANCTUM_STATEFUL_DOMAINS` | `goodwill-app.vercel.app` | ⚪ Optional (for cookie auth, but we use Bearer tokens) |

#### Super Admin
| Variable | Value | Required |
|----------|-------|----------|
| `SUPER_ADMIN_SECRET_KEY` | `your-secret-key` | ✅ Yes (for first super admin) |

---

## 🐳 DOCKER CONFIGURATION

### Dockerfile
Located at: `backend/Dockerfile`

**Key Features**:
- PHP 8.2 FPM
- Nginx reverse proxy
- Supervisor for process management
- Dynamic port binding (Render's PORT env var)
- Production optimizations

### Docker Files
- `backend/docker/nginx.conf` - Nginx configuration
- `backend/docker/supervisord.conf` - Supervisor configuration
- `backend/docker/start.sh` - Startup script

### Build & Test Locally
```bash
cd backend
docker build -t goodwill-backend .
docker run -p 10000:10000 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=your-db \
  -e DB_USERNAME=your-user \
  -e DB_PASSWORD=your-password \
  goodwill-backend
```

---

## 📝 VERCEL CONFIGURATION

### vercel.json
Located at: `frontend/vercel.json`

**Already configured**:
- SPA routing (all routes → index.html)
- Build command: `npm run build`
- Output directory: `dist`
- Asset caching headers

**No changes needed** ✅

---

## 🔍 HEALTH CHECK ENDPOINT

### Add to Backend

Create: `backend/routes/api.php` (add this route):

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
    ]);
});
```

**Purpose**: Render pings this endpoint every 5 minutes to prevent sleep.

---

## ⚠️ KNOWN LIMITATIONS (Free Tier)

### Render Free Tier
1. **Service Sleep**: After 15 minutes of inactivity, service sleeps
   - **Impact**: First request after sleep takes ~30-50 seconds
   - **Mitigation**: Health check endpoint (Render pings every 5 minutes)
   - **Acceptance**: Document this limitation to users

2. **No Background Workers**: Cannot run queue workers
   - **Impact**: Email sending is synchronous (may be slow)
   - **Mitigation**: Acceptable for low-volume apps
   - **Future**: Upgrade to paid tier for queues

3. **Cron Jobs Best-Effort**: May not run if service is sleeping
   - **Impact**: Email reminders may be delayed
   - **Mitigation**: Manual trigger from dashboard
   - **Acceptance**: Document this limitation

4. **No Persistent Storage**: Filesystem is ephemeral
   - **Impact**: Cannot store files locally
   - **Mitigation**: Use S3-compatible storage (already configured)
   - **Status**: ✅ Resolved

### Vercel Free Tier
1. **Build Time Limits**: 45 minutes per build
   - **Impact**: Large builds may timeout
   - **Mitigation**: Optimize build (already optimized)
   - **Status**: ✅ No issues expected

2. **Function Execution Time**: 10 seconds (Hobby plan)
   - **Impact**: N/A (static SPA)
   - **Status**: ✅ Not applicable

---

## 🧪 TESTING CHECKLIST

After deployment, test:

- [ ] Frontend loads correctly
- [ ] Backend health check works
- [ ] User registration works
- [ ] User login works
- [ ] Bearer token authentication works
- [ ] Avatar upload works (S3)
- [ ] Document upload works (S3)
- [ ] Credential CRUD operations work
- [ ] Email sending works (test with Mailtrap first)
- [ ] CORS allows Vercel domain
- [ ] Scheduler runs (check logs)
- [ ] Database migrations ran successfully

---

## 📊 MONITORING

### Render
- **Logs**: Dashboard → Your Service → Logs
- **Metrics**: Dashboard → Your Service → Metrics
- **Health**: Dashboard → Your Service → Health

### Vercel
- **Logs**: Dashboard → Your Project → Deployments → View Logs
- **Analytics**: Dashboard → Your Project → Analytics

---

## 🔄 UPDATING DEPLOYMENT

### Backend Updates
1. Push changes to GitHub
2. Render auto-deploys (or trigger manually)
3. Check logs for errors

### Frontend Updates
1. Push changes to GitHub
2. Vercel auto-deploys
3. Check deployment status

---

## 🆘 TROUBLESHOOTING

### Backend Issues

**Service won't start**:
- Check Docker logs in Render
- Verify environment variables
- Check database connection

**Database connection fails**:
- Verify database credentials
- Check database is running
- Verify network connectivity

**File upload fails**:
- Check S3 credentials
- Verify bucket exists
- Check bucket permissions

**CORS errors**:
- Verify `FRONTEND_URL` matches Vercel URL
- Check CORS config in `backend/config/cors.php`
- Clear config cache: `php artisan config:clear`

### Frontend Issues

**API calls fail**:
- Verify `VITE_API_BASE_URL` is correct
- Check browser console for errors
- Verify backend is running

**404 on refresh**:
- Verify `vercel.json` has SPA routing
- Check build output includes `index.html`

---

## 📚 ADDITIONAL RESOURCES

- [Render Documentation](https://render.com/docs)
- [Vercel Documentation](https://vercel.com/docs)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [AWS S3 Setup](https://aws.amazon.com/s3/)

---

## ✅ FINAL CHECKLIST

Before going live:

- [ ] All code changes applied (Storage, URLs, etc.)
- [ ] Environment variables set in both Vercel and Render
- [ ] S3 storage configured and tested
- [ ] SMTP configured and tested
- [ ] Database migrations ran successfully
- [ ] Health check endpoint works
- [ ] Frontend connects to backend
- [ ] Authentication works
- [ ] File uploads work (S3)
- [ ] Email sending works
- [ ] CORS configured correctly
- [ ] Documentation updated with limitations

---

**Deployment Status**: Ready for deployment after code changes ✅

**Estimated Deployment Time**: 30-60 minutes

**Support**: Check logs in Render and Vercel dashboards

