# Quick Deployment Guide

This is a quick reference for deploying the Goodwill Staffing application.

## 🚀 Quick Start

### Backend Deployment

**Windows:**
```powershell
cd backend
..\deploy-backend.ps1
```

**Linux/Mac:**
```bash
cd backend
chmod +x ../deploy-backend.sh
../deploy-backend.sh
```

### Frontend Deployment

**Windows:**
```powershell
cd frontend
..\deploy-frontend.ps1
```

**Linux/Mac:**
```bash
cd frontend
chmod +x ../deploy-frontend.sh
../deploy-frontend.sh
```

---

## 📋 Pre-Deployment Checklist

### Backend Requirements
- [ ] PHP 8.2+ installed
- [ ] Composer installed
- [ ] MySQL/PostgreSQL database created
- [ ] Web server (Nginx/Apache) configured
- [ ] SSL certificate obtained

### Frontend Requirements
- [ ] Node.js 18+ installed
- [ ] npm installed
- [ ] Production API URL determined

---

## 🔧 Environment Configuration

### Backend (.env)

Create `backend/.env` with these essential settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=goodwill_production
DB_USERNAME=your_user
DB_PASSWORD=your_password

FRONTEND_URL=https://yourdomain.com

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_key
MAIL_FROM_ADDRESS=noreply@yourdomain.com

SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
```

### Frontend (.env.production)

Create `frontend/.env.production`:

```env
VITE_API_BASE_URL=https://api.yourdomain.com/api
```

---

## 🌐 Deployment Platforms

### Frontend - Static Hosting

#### Vercel
1. Install: `npm i -g vercel`
2. Run: `cd frontend && vercel`
3. Or connect GitHub repo on vercel.com
4. Add env var: `VITE_API_BASE_URL`

#### Netlify
1. Connect repository on netlify.com
2. Build command: `npm run build`
3. Publish directory: `dist`
4. Add env var: `VITE_API_BASE_URL`

#### Cloudflare Pages
1. Connect repository
2. Build command: `npm run build`
3. Output directory: `dist`
4. Add env var: `VITE_API_BASE_URL`

### Backend - VPS/Server

#### Traditional Server
1. Upload backend files to server
2. Run deployment script
3. Configure Nginx/Apache
4. Set up SSL with Let's Encrypt
5. Configure cron job for scheduled tasks

#### Docker (Optional)
See `DEPLOYMENT.md` for Docker Compose setup.

---

## 📝 Post-Deployment Steps

1. **Test Backend API:**
   ```bash
   curl https://api.yourdomain.com/api/health
   ```

2. **Test Frontend:**
   - Open `https://yourdomain.com`
   - Test login/registration
   - Verify API connectivity

3. **Set Up Cron Job:**
   ```bash
   * * * * * cd /path/to/backend && php artisan schedule:run
   ```

4. **Set Up Backups:**
   - Database backups (daily)
   - File storage backups

5. **Monitor:**
   - Check Laravel logs: `storage/logs/laravel.log`
   - Set up uptime monitoring
   - Monitor error rates

---

## 🆘 Troubleshooting

### Backend Issues

**500 Error:**
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan config:cache
```

**Permission Issues:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Frontend Issues

**API Connection:**
- Verify `VITE_API_BASE_URL` in `.env.production`
- Check CORS settings in backend
- Verify backend is accessible

**404 on Routes:**
- Ensure SPA routing is configured
- Check web server configuration

---

## 📚 Detailed Documentation

- **Full Guide:** `DEPLOYMENT_STEPS.md`
- **Production Checklist:** `PRODUCTION_CHECKLIST.md`
- **Security Guide:** `SECURITY_AUDIT.md`

---

## 🎯 Common Commands

### Backend
```bash
# Deploy
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Frontend
```bash
# Build
npm install
npm run build

# Preview
npm run preview
```

---

## ✅ Verification Checklist

After deployment, verify:

- [ ] Backend API responds to `/api/health`
- [ ] Frontend loads without errors
- [ ] Login/registration works
- [ ] File uploads work (avatar, documents)
- [ ] Email sending works (if configured)
- [ ] SSL certificate is valid
- [ ] CORS is properly configured
- [ ] Scheduled tasks are running (cron)
- [ ] Backups are configured
- [ ] Error logging is working

---

**Need Help?** See `DEPLOYMENT_STEPS.md` for detailed instructions.

