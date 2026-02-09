# Deployment Files Overview

This document lists all deployment-related files and their purposes.

## 📁 Deployment Scripts

### Windows (PowerShell)
- **`deploy-backend.ps1`** - Automated backend deployment script for Windows
  - Checks prerequisites (PHP, Composer)
  - Installs dependencies
  - Configures environment
  - Runs migrations
  - Optimizes for production

- **`deploy-frontend.ps1`** - Automated frontend deployment script for Windows
  - Checks prerequisites (Node.js, npm)
  - Configures environment
  - Installs dependencies
  - Builds for production

### Linux/Mac (Bash)
- **`deploy-backend.sh`** - Automated backend deployment script for Linux/Mac
  - Same functionality as PowerShell version
  - Includes permission setting

- **`deploy-frontend.sh`** - Automated frontend deployment script for Linux/Mac
  - Same functionality as PowerShell version

**Usage:**
```bash
# Make executable (Linux/Mac only)
chmod +x deploy-backend.sh deploy-frontend.sh

# Run
./deploy-backend.sh
./deploy-frontend.sh
```

---

## 📚 Documentation Files

### Main Guides
- **`DEPLOYMENT_STEPS.md`** - Comprehensive step-by-step deployment guide
  - Prerequisites
  - Backend deployment (manual and script)
  - Frontend deployment (multiple platforms)
  - Web server configuration (Nginx/Apache)
  - SSL setup
  - Post-deployment tasks
  - Troubleshooting

- **`QUICK_DEPLOY.md`** - Quick reference guide
  - Fast deployment commands
  - Essential checklists
  - Common commands
  - Quick troubleshooting

- **`DEPLOYMENT_GUIDE.md`** - Original deployment guide (existing)
  - Production checklist
  - Environment configuration
  - Security settings

- **`DEPLOYMENT.md`** - Alternative deployment guide (existing)
  - Docker deployment
  - CI/CD examples

### Reference Documents
- **`PRODUCTION_CHECKLIST.md`** - Production readiness checklist
- **`SECURITY_AUDIT.md`** - Security considerations

---

## ⚙️ Configuration Files

### Frontend
- **`frontend/vercel.json`** - Vercel deployment configuration
  - Build settings
  - SPA routing
  - Cache headers

- **`frontend/netlify.toml`** - Netlify deployment configuration
  - Build settings
  - Redirect rules
  - Cache headers

- **`frontend/.env.production.example`** - Production environment template
  - API URL configuration

### Backend
- **`backend/.env.production.example`** - Production environment template
  - Database configuration
  - Mail settings
  - Session settings
  - Security settings

---

## 🎯 Quick Start

### For First-Time Deployment

1. **Read:** `QUICK_DEPLOY.md` for overview
2. **Follow:** `DEPLOYMENT_STEPS.md` for detailed instructions
3. **Use:** Deployment scripts for automation
4. **Check:** `PRODUCTION_CHECKLIST.md` before going live

### For Quick Updates

1. **Backend:** Run `deploy-backend.ps1` or `deploy-backend.sh`
2. **Frontend:** Run `deploy-frontend.ps1` or `deploy-frontend.sh`

---

## 📋 File Locations

```
goodwill/
├── deploy-backend.ps1          # Windows backend script
├── deploy-frontend.ps1          # Windows frontend script
├── deploy-backend.sh            # Linux/Mac backend script
├── deploy-frontend.sh           # Linux/Mac frontend script
├── DEPLOYMENT_STEPS.md          # Comprehensive guide
├── QUICK_DEPLOY.md              # Quick reference
├── DEPLOYMENT_GUIDE.md          # Original guide
├── DEPLOYMENT.md                # Alternative guide
├── PRODUCTION_CHECKLIST.md      # Production checklist
├── frontend/
│   ├── vercel.json              # Vercel config
│   ├── netlify.toml             # Netlify config
│   └── .env.production.example   # Frontend env template
└── backend/
    └── .env.production.example   # Backend env template
```

---

## 🔄 Deployment Workflow

### Initial Deployment
1. Configure environment files (`.env` and `.env.production`)
2. Run backend deployment script
3. Configure web server
4. Run frontend deployment script
5. Deploy frontend build to hosting
6. Test and verify

### Updates
1. Pull latest code
2. Run deployment scripts
3. Clear caches if needed
4. Test changes

---

## 💡 Tips

- **Always test in staging first** before production
- **Backup database** before running migrations
- **Keep environment files secure** (never commit `.env`)
- **Monitor logs** after deployment
- **Set up automated backups** for production

---

## 🆘 Need Help?

1. Check `DEPLOYMENT_STEPS.md` troubleshooting section
2. Review error logs (Laravel: `storage/logs/laravel.log`)
3. Verify environment configuration
4. Check web server configuration
5. Review `PRODUCTION_CHECKLIST.md` for common issues

