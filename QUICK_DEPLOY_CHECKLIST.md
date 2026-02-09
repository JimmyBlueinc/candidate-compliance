# ✅ Quick Deploy Checklist

## Before You Start

- [ ] Read `DEPLOYMENT_SUMMARY.md` for overview
- [ ] Read `DEPLOYMENT_CODE_FIXES.md` for required code changes
- [ ] Have S3 storage account ready (AWS S3, DigitalOcean Spaces, etc.)
- [ ] Have SMTP account ready (SendGrid, Mailgun, etc.)

---

## Step 1: Apply Code Fixes (15-30 minutes)

### Fix Storage Disk Usage
- [ ] Update `AuthController.php` (2 changes)
- [ ] Update `CredentialController.php` (1 change)
- [ ] Update `BackgroundCheckController.php` (2 changes)
- [ ] Update `HealthRecordController.php` (2 changes)
- [ ] Update `WorkAuthorizationController.php` (2 changes)

**Change**: `Storage::disk('public')` → `Storage::disk(config('filesystems.default'))`

### Fix Model URL Accessors
- [ ] Update `User.php` - `getAvatarUrlAttribute()`
- [ ] Update `Credential.php` - `getDocumentUrlAttribute()`
- [ ] Update `BackgroundCheck.php` - `getDocumentUrlAttribute()`
- [ ] Update `HealthRecord.php` - `getDocumentUrlAttribute()`
- [ ] Update `WorkAuthorization.php` - `getDocumentUrlAttribute()`
- [ ] Update `TrainingRecord.php` - `getDocumentUrlAttribute()`
- [ ] Update `PerformanceRecord.php` - `getDocumentUrlAttribute()`

**Change**: Use `Storage::url()` instead of manual URL construction

**Reference**: See `DEPLOYMENT_CODE_FIXES.md` for exact code

---

## Step 2: Commit & Push (2 minutes)

```bash
git add .
git commit -m "Prepare for Render + Vercel deployment"
git push origin main
```

- [ ] Code fixes committed
- [ ] Docker files committed
- [ ] Config updates committed
- [ ] Pushed to GitHub

---

## Step 3: Deploy Backend to Render (10-15 minutes)

### Create PostgreSQL Database
- [ ] Render Dashboard → New + → PostgreSQL
- [ ] Name: `goodwill-db`
- [ ] Plan: Free
- [ ] Copy database credentials

### Create Web Service
- [ ] Render Dashboard → New + → Web Service
- [ ] Connect GitHub repository
- [ ] Configure:
  - [ ] Name: `goodwill-backend`
  - [ ] Environment: Docker
  - [ ] Root Directory: `backend`
  - [ ] Dockerfile Path: `backend/Dockerfile`
  - [ ] Plan: Free
- [ ] Set Environment Variables (see below)
- [ ] Health Check Path: `/api/health`
- [ ] Deploy

### Backend Environment Variables (25+)
- [ ] `APP_NAME=Goodwill Staffing Compliance Tracker`
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY=base64:...` (generate with `php artisan key:generate`)
- [ ] `APP_URL=https://your-backend.onrender.com`
- [ ] `FRONTEND_URL=https://your-app.vercel.app`
- [ ] `VERCEL_URL=https://your-app.vercel.app`
- [ ] Database variables (from Render)
- [ ] S3 storage variables
- [ ] SMTP mail variables
- [ ] `SUPER_ADMIN_SECRET_KEY=...`

**Full list**: See `DEPLOYMENT_VERCEL_RENDER.md`

- [ ] Backend deployed successfully
- [ ] Health check works: `https://your-backend.onrender.com/api/health`
- [ ] Copy backend URL

---

## Step 4: Deploy Frontend to Vercel (5-10 minutes)

- [ ] Vercel Dashboard → Add New → Project
- [ ] Import GitHub repository
- [ ] Configure:
  - [ ] Framework: Vite
  - [ ] Root Directory: `frontend`
  - [ ] Build Command: `npm run build`
  - [ ] Output Directory: `dist`
- [ ] Set Environment Variable:
  - [ ] `VITE_API_BASE_URL=https://your-backend.onrender.com/api`
- [ ] Deploy

- [ ] Frontend deployed successfully
- [ ] Frontend loads correctly
- [ ] Copy frontend URL

---

## Step 5: Configure Cron Job (2 minutes)

- [ ] Render Dashboard → New + → Cron Job
- [ ] Name: `goodwill-scheduler`
- [ ] Schedule: `* * * * *` (every minute)
- [ ] Command: `cd backend && php artisan schedule:run`
- [ ] Service: Select your backend service
- [ ] Plan: Free
- [ ] Create

---

## Step 6: Update Backend CORS (2 minutes)

- [ ] Render Dashboard → Backend Service → Environment
- [ ] Update `FRONTEND_URL` with actual Vercel URL
- [ ] Update `VERCEL_URL` with actual Vercel URL
- [ ] Redeploy backend (or wait for auto-deploy)

---

## Step 7: Test Everything (10 minutes)

### Basic Tests
- [ ] Frontend loads: `https://your-app.vercel.app`
- [ ] Backend health: `https://your-backend.onrender.com/api/health`
- [ ] Frontend connects to backend (check browser console)

### Authentication Tests
- [ ] User registration works
- [ ] User login works
- [ ] Bearer token stored correctly
- [ ] Protected routes work

### File Upload Tests
- [ ] Avatar upload works
- [ ] Document upload works
- [ ] Files appear in S3 bucket
- [ ] File URLs are accessible (S3 URLs)

### Feature Tests
- [ ] Credential CRUD operations work
- [ ] Search/filter works
- [ ] Dashboard loads
- [ ] Email sending works (test with Mailtrap first)

### Scheduler Test
- [ ] Check Render logs for cron job execution
- [ ] Verify scheduler runs (may be delayed due to sleep)

---

## Step 8: Final Verification (5 minutes)

- [ ] All tests pass
- [ ] No errors in browser console
- [ ] No errors in Render logs
- [ ] No errors in Vercel logs
- [ ] Files upload to S3 correctly
- [ ] Emails send correctly
- [ ] CORS works (no CORS errors)

---

## 🎉 Deployment Complete!

### Your URLs
- **Frontend**: `https://your-app.vercel.app`
- **Backend**: `https://your-backend.onrender.com/api`
- **Health Check**: `https://your-backend.onrender.com/api/health`

### Next Steps
- [ ] Create first super admin account
- [ ] Test all features
- [ ] Monitor logs for any issues
- [ ] Document any limitations to users

---

## 🆘 If Something Goes Wrong

1. **Check Logs**:
   - Render: Dashboard → Service → Logs
   - Vercel: Dashboard → Project → Deployments → Logs

2. **Common Issues**:
   - **Backend won't start**: Check environment variables, Docker logs
   - **File upload fails**: Check S3 credentials, bucket permissions
   - **CORS errors**: Verify `FRONTEND_URL` matches Vercel URL
   - **Database errors**: Check database credentials, connection

3. **Reference**:
   - `DEPLOYMENT_VERCEL_RENDER.md` → Troubleshooting section
   - `DEPLOYMENT_CODE_FIXES.md` → Verify all fixes applied

---

**Total Estimated Time**: 45-75 minutes

**Status**: Ready to deploy! ✅

