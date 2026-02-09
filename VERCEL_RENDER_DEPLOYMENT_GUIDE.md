# 🚀 Vercel + Render Deployment Guide
## Step-by-Step Deployment Instructions

**Status**: ✅ Code fixes already applied and pushed to GitHub

This guide will walk you through deploying your Goodwill Staffing app to:
- **Frontend**: Vercel (Free Tier)
- **Backend**: Render (Free Tier)

---

## 📋 Prerequisites Checklist

Before starting, ensure you have:

- [ ] GitHub repository pushed with all changes
- [ ] S3-compatible storage account ready (AWS S3, DigitalOcean Spaces, or Backblaze B2)
- [ ] SMTP email account ready (SendGrid or Mailgun)
- [ ] GitHub account connected to both Vercel and Render

---

## PART 1: Set Up S3 Storage (Choose One Option)

### Option A: AWS S3 (Free Tier - 5GB for 12 months) ⭐ Recommended

1. **Go to**: https://aws.amazon.com/s3/
2. **Sign up** for AWS account (if needed)
3. **Go to S3 Console**: https://console.aws.amazon.com/s3/
4. **Click "Create bucket"**
5. **Configure**:
   - **Bucket name**: `goodwill-storage-xxxxx` (must be globally unique, add random numbers)
   - **Region**: Choose closest (e.g., `us-east-1`)
   - **Block Public Access**: **Uncheck** "Block all public access" ⚠️
   - Click "Create bucket"

6. **Create IAM User**:
   - Go to: https://console.aws.amazon.com/iam/
   - Click "Users" → "Create user"
   - **Name**: `goodwill-s3-user`
   - **Access type**: "Programmatic access"
   - Click "Next: Permissions"
   - Select: `AmazonS3FullAccess`
   - Click "Next" → "Create user"

7. **Save Credentials** (you can't see secret again):
   - **Access Key ID**: `AKIA...` ← Copy this
   - **Secret Access Key**: `xxxxx` ← Copy this NOW

8. **Configure Bucket CORS**:
   - Go to bucket → "Permissions" tab
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

9. **Write down your S3 details**:
   ```
   AWS_ACCESS_KEY_ID = AKIA...
   AWS_SECRET_ACCESS_KEY = xxxxx
   AWS_DEFAULT_REGION = us-east-1
   AWS_BUCKET = goodwill-storage-xxxxx
   AWS_ENDPOINT = https://s3.amazonaws.com
   ```

---

### Option B: DigitalOcean Spaces ($5/month, 250GB)

1. **Go to**: https://www.digitalocean.com/products/spaces
2. **Sign up** for account
3. **Create Space**:
   - Name: `goodwill-storage` (must be unique)
   - Region: Choose closest
   - Click "Create a Space"

4. **Create API Key**:
   - Go to: https://cloud.digitalocean.com/account/api/spaces
   - Click "Generate New Key"
   - **Name**: `goodwill-spaces-key`
   - **Access**: "Read and Write"
   - Click "Generate Key"
   - **Save Access Key and Secret Key**

5. **Your Spaces Details**:
   ```
   AWS_ACCESS_KEY_ID = (your Spaces access key)
   AWS_SECRET_ACCESS_KEY = (your Spaces secret key)
   AWS_DEFAULT_REGION = nyc3 (or your region)
   AWS_BUCKET = goodwill-storage
   AWS_ENDPOINT = https://nyc3.digitaloceanspaces.com
   AWS_USE_PATH_STYLE_ENDPOINT = true
   ```

---

## PART 2: Set Up SMTP Email (Choose One Option)

### Option A: SendGrid (Free Tier - 100 emails/day) ⭐ Recommended

1. **Go to**: https://sendgrid.com/
2. **Sign up** for free account
3. **Verify your email** (check inbox)
4. **Create API Key**:
   - Go to: https://app.sendgrid.com/settings/api_keys
   - Click "Create API Key"
   - **Name**: `goodwill-api-key`
   - **Permissions**: "Full Access"
   - Click "Create & View"
   - **Copy API Key** (starts with `SG.`)

5. **Verify Sender**:
   - Go to: https://app.sendgrid.com/settings/sender_auth/senders/new
   - **From Email**: `noreply@yourdomain.com` (or use your email)
   - **From Name**: `Goodwill Staffing`
   - Fill in details and verify email

6. **Your SendGrid Details**:
   ```
   MAIL_HOST = smtp.sendgrid.net
   MAIL_PORT = 587
   MAIL_USERNAME = apikey
   MAIL_PASSWORD = SG.xxxxx (your API key)
   MAIL_ENCRYPTION = tls
   MAIL_FROM_ADDRESS = noreply@yourdomain.com
   MAIL_FROM_NAME = Goodwill Staffing
   ```

---

### Option B: Mailgun (Free Tier - 5,000 emails/month)

1. **Go to**: https://www.mailgun.com/
2. **Sign up** for free account
3. **Verify email**
4. **Add Domain** (or use sandbox for testing)
5. **Get API Key**:
   - Go to: https://app.mailgun.com/app/account/security/api_keys
   - Copy "Private API key"

6. **Your Mailgun Details**:
   ```
   MAIL_HOST = smtp.mailgun.org
   MAIL_PORT = 587
   MAIL_USERNAME = postmaster@your-domain.mailgun.org
   MAIL_PASSWORD = xxxxx (your Private API key)
   MAIL_ENCRYPTION = tls
   MAIL_FROM_ADDRESS = noreply@your-domain.mailgun.org
   MAIL_FROM_NAME = Goodwill Staffing
   ```

---

## PART 3: Deploy Backend to Render

### Step 3.1: Create PostgreSQL Database

1. **Go to**: https://dashboard.render.com
2. **Sign up/Login** (use GitHub account)
3. **Click "New +"** (top right) → **"PostgreSQL"**
4. **Configure**:
   - **Name**: `goodwill-db`
   - **Database**: `goodwill` (auto-filled)
   - **User**: `goodwill_user` (auto-filled)
   - **Region**: Choose closest
   - **PostgreSQL Version**: Latest
   - **Plan**: **Free**
   - Click "Create Database"

5. **Wait 1-2 minutes** for database creation

6. **Copy Database Credentials**:
   - Click on your database
   - **Copy these values**:
     - **Host**: `dpg-xxxxx-a.render.com`
     - **Port**: `5432`
     - **Database**: `goodwill_xxxxx`
     - **User**: `goodwill_user`
     - **Password**: `xxxxx` (click "Show" to reveal)

---

### Step 3.2: Create Web Service (Backend)

1. **In Render Dashboard**, click **"New +"** → **"Web Service"**

2. **Connect GitHub**:
   - Click "Connect account" if not connected
   - Authorize Render
   - Select repository: `goodwillstaffing`
   - Click "Connect"

3. **Configure Service**:
   - **Name**: `goodwill-backend`
   - **Region**: Same as database
   - **Branch**: `main`
   - **Root Directory**: `backend` ⚠️ **IMPORTANT**
   - **Runtime**: `Docker`
   - **Dockerfile Path**: `backend/Dockerfile` ⚠️ **IMPORTANT**
   - **Docker Context**: `backend` ⚠️ **IMPORTANT**
   - **Plan**: **Free**
   - Click "Create Web Service"

4. **Wait for initial build** (5-10 minutes)

---

### Step 3.3: Set Environment Variables

1. **In your Web Service**, go to **"Environment"** tab

2. **Add these variables** (click "Add Environment Variable" for each):

#### Application Variables:
```
APP_NAME = Goodwill Staffing Compliance Tracker
APP_ENV = production
APP_DEBUG = false
APP_KEY = (generate this - see Step 3.4 below)
APP_URL = https://goodwill-backend.onrender.com (use YOUR service URL)
LOG_LEVEL = error
```

#### Frontend/CORS Variables (update after frontend deploys):
```
FRONTEND_URL = https://your-app.vercel.app (update after Part 4)
VERCEL_URL = https://your-app.vercel.app (update after Part 4)
```

#### Database Variables (from Step 3.1):
```
DB_CONNECTION = pgsql
DB_HOST = dpg-xxxxx-a.render.com
DB_PORT = 5432
DB_DATABASE = goodwill_xxxxx
DB_USERNAME = goodwill_user
DB_PASSWORD = xxxxx
```

#### Storage/S3 Variables (from Part 1):
```
FILESYSTEM_DISK = s3
AWS_ACCESS_KEY_ID = AKIA... (from S3 setup)
AWS_SECRET_ACCESS_KEY = xxxxx (from S3 setup)
AWS_DEFAULT_REGION = us-east-1 (or your region)
AWS_BUCKET = goodwill-storage-xxxxx (your bucket name)
AWS_ENDPOINT = https://s3.amazonaws.com (or your endpoint)
AWS_USE_PATH_STYLE_ENDPOINT = false (or true for S3-compatible)
```

#### Mail/SMTP Variables (from Part 2):
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
SUPER_ADMIN_SECRET_KEY = (generate random string - see Step 3.5)
```

3. **Click "Save Changes"** after adding all variables

---

### Step 3.4: Generate APP_KEY

1. **Open terminal/command prompt**

2. **Navigate to backend**:
   ```bash
   cd C:\Users\MIKENZY\Documents\Apps\goodwill\backend
   ```

3. **Generate key**:
   ```bash
   php artisan key:generate --show
   ```

4. **Copy the output** (looks like `base64:xxxxx...`)

5. **In Render** → Environment tab → Update `APP_KEY` with this value

6. **Click "Save Changes"**

---

### Step 3.5: Generate SUPER_ADMIN_SECRET_KEY

1. **Open PowerShell** (Windows) or Terminal (Mac/Linux)

2. **Generate random string** (PowerShell):
   ```powershell
   -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | ForEach-Object {[char]$_})
   ```

   Or use online: https://www.random.org/strings/

3. **Copy the generated string** (32+ characters)

4. **In Render** → Environment tab → Set `SUPER_ADMIN_SECRET_KEY` to this value

5. **Click "Save Changes"**

---

### Step 3.6: Configure Health Check

1. **In your Web Service**, go to **"Settings"** tab

2. **Scroll to "Health Check Path"**

3. **Enter**: `/api/health`

4. **Click "Save Changes"**

---

### Step 3.7: Deploy Backend

1. **Go to "Events" tab** in your Web Service

2. **Click "Manual Deploy"** → **"Deploy latest commit"**

3. **Wait for deployment** (5-10 minutes)

4. **Check logs**:
   - Go to "Logs" tab
   - Look for "Build successful" or errors

5. **Test health check**:
   - Copy your service URL (e.g., `https://goodwill-backend.onrender.com`)
   - Visit: `https://goodwill-backend.onrender.com/api/health`
   - Should see: `{"status":"ok","timestamp":"...","environment":"production"}`

6. **Copy your backend URL** (you'll need it for frontend)

---

## PART 4: Deploy Frontend to Vercel

### Step 4.1: Create Vercel Account

1. **Go to**: https://vercel.com
2. **Sign up** (use GitHub account)
3. **Authorize** Vercel to access your GitHub

---

### Step 4.2: Import Project

1. **In Vercel Dashboard**, click **"Add New..."** → **"Project"**

2. **Import Git Repository**:
   - Find your repository: `goodwillstaffing`
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

### Step 4.3: Set Environment Variable

1. **After deployment**, go to your project

2. **Click "Settings"** → **"Environment Variables"**

3. **Add Variable**:
   - **Key**: `VITE_API_BASE_URL`
   - **Value**: `https://goodwill-backend.onrender.com/api` (use YOUR backend URL from Part 3)
   - **Environment**: Select all (Production, Preview, Development)
   - Click **"Save"**

4. **Redeploy**:
   - Go to **"Deployments"** tab
   - Click **"..."** on latest deployment → **"Redeploy"**
   - Or push a new commit to trigger redeploy

5. **Wait for redeployment** (2-5 minutes)

6. **Copy your frontend URL** (e.g., `https://goodwill-app.vercel.app`)

---

### Step 4.4: Update Backend CORS

1. **Go back to Render** → Your Backend Service

2. **Go to "Environment"** tab

3. **Update these variables**:
   - `FRONTEND_URL` = `https://your-app.vercel.app` (use YOUR Vercel URL)
   - `VERCEL_URL` = `https://your-app.vercel.app` (use YOUR Vercel URL)

4. **Click "Save Changes"**

5. **Redeploy backend**:
   - Go to "Events" tab
   - Click "Manual Deploy" → "Deploy latest commit"

---

## PART 5: Configure Cron Job (Scheduler)

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

## PART 6: Testing

### Step 6.1: Test Frontend

1. **Visit your Vercel URL**: `https://your-app.vercel.app`

2. **Check if it loads** (should see login page)

3. **Open browser console** (F12) and check for errors

---

### Step 6.2: Test Backend

1. **Test health check**:
   - Visit: `https://your-backend.onrender.com/api/health`
   - Should see: `{"status":"ok",...}`

2. **Test API**:
   - Visit: `https://your-backend.onrender.com/api/register`
   - Should see validation error (expected - means API is working)

---

### Step 6.3: Test Registration

1. **Go to frontend**: `https://your-app.vercel.app`

2. **Click "Register"** (or "Sign Up")

3. **Fill in form**:
   - Name: `Test User`
   - Email: `test@example.com`
   - Password: `password123`
   - Confirm Password: `password123`
   - Role: `admin`

4. **Submit**

5. **Check if registration works** (should redirect to dashboard)

---

### Step 6.4: Test Login

1. **Use credentials from registration**

2. **Login**

3. **Check if you're logged in** (should see dashboard)

---

### Step 6.5: Test File Upload

1. **After logging in**, go to **Settings/Profile**

2. **Upload an avatar image**

3. **Check**:
   - Does upload complete?
   - Does image appear?
   - Check browser console for errors
   - Check S3 bucket - file should be there

---

### Step 6.6: Test Credential Creation

1. **Go to Dashboard**

2. **Click "Add Credential"**

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

### Step 6.7: Test Email (Optional)

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

## PART 7: Create First Super Admin

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

- [ ] S3 storage set up (Part 1)
- [ ] SMTP email set up (Part 2)
- [ ] Backend deployed to Render (Part 3)
- [ ] All environment variables set in Render
- [ ] Frontend deployed to Vercel (Part 4)
- [ ] Environment variable set in Vercel
- [ ] CORS updated in backend
- [ ] Cron job configured (Part 5)
- [ ] All tests pass (Part 6)
- [ ] Super admin created (Part 7)

---

## 🆘 Troubleshooting

### Backend Won't Start
- **Check Render Logs**: Dashboard → Service → Logs
- **Check environment variables** are all set
- **Check database connection** (verify credentials)
- **Check Docker build** logs for errors

### Frontend Can't Connect
- **Check `VITE_API_BASE_URL`** is correct
- **Check backend is running** (test health endpoint)
- **Check browser console** for CORS errors
- **Verify CORS** in backend environment variables

### File Upload Fails
- **Check S3 credentials** in Render
- **Check bucket exists** and is accessible
- **Check bucket permissions** (public read)
- **Check code fixes** were applied (already done ✅)

### Email Not Sending
- **Check SMTP credentials** in Render
- **Test with Mailtrap first** (easier to debug)
- **Check email logs** in Render
- **Verify sender email** is verified (SendGrid/Mailgun)

---

## 📊 Your Deployment URLs

After deployment, you'll have:

- **Frontend**: `https://your-app.vercel.app`
- **Backend API**: `https://your-backend.onrender.com/api`
- **Health Check**: `https://your-backend.onrender.com/api/health`

---

## 🎉 Deployment Complete!

Your application is now live on:
- **Frontend**: Vercel (Free Tier)
- **Backend**: Render (Free Tier)
- **Database**: Render PostgreSQL (Free Tier)
- **Storage**: S3-compatible (your choice)
- **Email**: SMTP (your choice)

**Note**: Render free services may sleep after 15 minutes of inactivity. First request after sleep takes ~30-50 seconds to wake up.

---

**Need Help?** Check the logs in Render and Vercel dashboards for detailed error messages.

