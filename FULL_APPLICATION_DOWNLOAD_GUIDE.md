# 📦 Complete Application Download & Setup Guide
## Goodwill Staffing Compliance Tracker

**Repository**: https://github.com/Jim-devENG/goodwillstaffing.git  
**Version**: 1.0  
**Status**: Production Ready  
**Last Updated**: 2025

---

## 🚀 Quick Download Instructions

### Option 1: Clone via Git (Recommended)
```bash
git clone https://github.com/Jim-devENG/goodwillstaffing.git
cd goodwillstaffing
```

### Option 2: Download ZIP
1. Visit: https://github.com/Jim-devENG/goodwillstaffing
2. Click the green "Code" button
3. Select "Download ZIP"
4. Extract the ZIP file to your desired location

---

## 📋 Complete Application Structure

```
goodwillstaffing/
│
├── 📁 backend/                          # Laravel 12 Backend API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/
│   │   │   │   │   ├── AuthController.php          # Authentication (login, register, logout)
│   │   │   │   │   ├── CredentialController.php    # Credential CRUD operations
│   │   │   │   │   ├── SuperAdminController.php    # Super admin management
│   │   │   │   │   ├── AnalyticsController.php     # Dashboard analytics
│   │   │   │   │   ├── ActivityLogController.php   # Activity logging
│   │   │   │   │   ├── BackgroundCheckController.php
│   │   │   │   │   ├── HealthRecordController.php
│   │   │   │   │   ├── WorkAuthorizationController.php
│   │   │   │   │   └── ... (more controllers)
│   │   │   │   └── Controller.php
│   │   │   ├── Middleware/
│   │   │   │   ├── SecurityHeaders.php             # Security headers
│   │   │   │   └── RoleMiddleware.php              # Role-based access
│   │   │   └── Requests/
│   │   │       ├── StoreCredentialRequest.php      # Validation rules
│   │   │       └── UpdateCredentialRequest.php
│   │   ├── Models/
│   │   │   ├── User.php                            # User model with roles
│   │   │   ├── Credential.php                     # Credential model
│   │   │   ├── BackgroundCheck.php
│   │   │   ├── HealthRecord.php
│   │   │   ├── WorkAuthorization.php
│   │   │   └── ... (more models)
│   │   ├── Mail/
│   │   │   ├── CredentialExpiryReminder.php        # Email templates
│   │   │   └── CredentialExpirySummary.php
│   │   └── Console/
│   │       └── Commands/
│   │           └── SendExpiryReminders.php         # Scheduled tasks
│   ├── database/
│   │   ├── migrations/                            # Database schema
│   │   │   ├── create_users_table.php
│   │   │   ├── create_credentials_table.php
│   │   │   ├── create_background_checks_table.php
│   │   │   ├── create_health_records_table.php
│   │   │   └── ... (22 migrations total)
│   │   ├── factories/                             # Test data factories
│   │   └── seeders/                               # Database seeders
│   ├── routes/
│   │   └── api.php                                # API routes
│   ├── config/                                    # Configuration files
│   ├── resources/
│   │   └── views/
│   │       └── emails/                            # Email templates (Blade)
│   ├── storage/                                   # File storage
│   ├── public/                                    # Public assets
│   ├── .env.example                              # Environment template
│   ├── composer.json                             # PHP dependencies
│   └── artisan                                   # Laravel CLI
│
├── 📁 frontend/                                  # React 19 Frontend
│   ├── src/
│   │   ├── components/
│   │   │   ├── Layout/
│   │   │   │   ├── Sidebar.jsx                   # Navigation sidebar
│   │   │   │   ├── Topbar.jsx                    # Header with user info
│   │   │   │   └── ProtectedRoute.jsx             # Route protection
│   │   │   ├── CredentialForm.jsx                # Credential form modal
│   │   │   ├── StatusCard.jsx                    # Status statistics cards
│   │   │   ├── StatusTag.jsx                     # Status badges
│   │   │   ├── QuickFilters.jsx                  # Filter buttons
│   │   │   ├── WelcomeBanner.jsx                  # Dashboard welcome section
│   │   │   ├── BackgroundCheckForm.jsx
│   │   │   ├── HealthRecordForm.jsx
│   │   │   └── ... (more components)
│   │   ├── pages/
│   │   │   ├── Login.jsx                         # Login page
│   │   │   ├── Dashboard.jsx                    # Main dashboard
│   │   │   ├── CredentialTracker.jsx             # Credential list view
│   │   │   ├── Settings.jsx                       # User settings
│   │   │   ├── CreateSuperAdmin.jsx              # Super admin creation
│   │   │   ├── Analytics.jsx                     # Analytics dashboard
│   │   │   ├── CandidateRegistration.jsx
│   │   │   ├── ComplianceDashboard.jsx
│   │   │   └── ... (more pages)
│   │   ├── contexts/
│   │   │   ├── AuthContext.jsx                   # Authentication state
│   │   │   └── ThemeContext.jsx                  # Theme management
│   │   ├── config/
│   │   │   └── api.js                            # Axios configuration
│   │   ├── hooks/
│   │   │   └── useFetchCredentials.js            # Custom React hooks
│   │   ├── App.jsx                               # Main app component
│   │   ├── index.jsx                             # Entry point
│   │   └── index.css                             # Global styles
│   ├── public/                                   # Static assets
│   ├── dist/                                     # Production build output
│   ├── .env.example                              # Environment template
│   ├── package.json                              # Node dependencies
│   ├── vite.config.js                            # Vite configuration
│   └── tailwind.config.js                        # Tailwind CSS config
│
├── 📄 README.md                                  # Main documentation
├── 📄 COMPREHENSIVE_APP_BREAKDOWN.md             # Detailed breakdown
├── 📄 DEPLOYMENT_GUIDE.md                        # Deployment instructions
├── 📄 SECURITY_AUDIT.md                         # Security documentation
├── 📄 .gitignore                                 # Git ignore rules
└── 📄 LICENSE                                    # MIT License

```

---

## 🎯 Core Features

### 1. Authentication & Authorization
- ✅ Token-based authentication (Laravel Sanctum)
- ✅ User registration with role assignment
- ✅ Login with "Remember Me" option (30-day tokens)
- ✅ Password reset via email
- ✅ Role-based access control (Super Admin, Admin, Recruiter)
- ✅ Profile management with avatar upload
- ✅ Real-time token validation

### 2. Credential Management
- ✅ Create, Read, Update, Delete credentials
- ✅ Automatic status calculation (Active/Expiring Soon/Expired)
- ✅ Document upload (PDF, DOC, DOCX)
- ✅ Search and filter functionality
- ✅ Pagination for large datasets
- ✅ CSV export functionality
- ✅ Province and specialty tracking

### 3. Dashboard & Analytics
- ✅ Real-time statistics cards
- ✅ Status distribution charts
- ✅ Credentials by type visualization
- ✅ Quick filter buttons
- ✅ Recent credentials list
- ✅ Customizable welcome banner
- ✅ Medical-themed animations

### 4. Email System
- ✅ Automated expiry reminders (30, 14, 7 days before expiry)
- ✅ Daily summary emails for administrators
- ✅ Beautiful email templates (Blade)
- ✅ Configurable email settings
- ✅ Manual email trigger option

### 5. Additional Features
- ✅ Activity logging
- ✅ Background checks management
- ✅ Health records tracking
- ✅ Work authorization management
- ✅ Candidate registration
- ✅ Compliance dashboard
- ✅ Advanced filtering
- ✅ Bulk operations
- ✅ Calendar view
- ✅ Reports generation
- ✅ Templates system
- ✅ Import/Export functionality

---

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 12
- **Language**: PHP 8.2+
- **Database**: SQLite (dev) / MySQL (production)
- **Authentication**: Laravel Sanctum
- **Email**: Laravel Mail with Markdown
- **Scheduling**: Laravel Task Scheduler
- **Validation**: Laravel Form Requests

### Frontend
- **Framework**: React 19
- **Build Tool**: Vite 7
- **Styling**: Tailwind CSS 3.4
- **HTTP Client**: Axios
- **Routing**: React Router DOM 7
- **Icons**: Lucide React
- **State Management**: React Context API

---

## 📦 Installation Steps

### Prerequisites
- PHP >= 8.2 with required extensions
- Composer >= 2.0
- Node.js >= 18.0
- npm >= 9.0
- Git
- MySQL (production) or SQLite (development)

### Step 1: Clone Repository
```bash
git clone https://github.com/Jim-devENG/goodwillstaffing.git
cd goodwillstaffing
```

### Step 2: Backend Setup
```bash
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database (SQLite for development)
touch database/database.sqlite

# Or configure MySQL in .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=goodwill_db
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Create storage symlink
php artisan storage:link
```

### Step 3: Frontend Setup
```bash
cd ../frontend

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env
```

### Step 4: Configure Environment

**Backend `.env` file:**
```env
APP_NAME="Goodwill Staffing Compliance Tracker"
APP_ENV=local
APP_KEY=base64:... (generated by key:generate)
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/backend/database/database.sqlite

FRONTEND_URL=http://localhost:3000

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Super Admin Secret Key (required for first super admin)
SUPER_ADMIN_SECRET_KEY=your-secret-key-here
```

**Frontend `.env` file:**
```env
VITE_API_BASE_URL=http://localhost:8000/api
```

### Step 5: Setup Super Admin Secret Key

**Windows:**
```bash
# PowerShell
powershell -ExecutionPolicy Bypass -File add-super-admin-key.ps1

# Or Batch
add-super-admin-key.bat
```

**Linux/Mac:**
```bash
# Generate a random key
php -r "echo 'SUPER_ADMIN_SECRET_KEY=' . bin2hex(random_bytes(32)) . PHP_EOL;"

# Add to backend/.env manually
```

### Step 6: Run the Application

**Terminal 1 - Backend:**
```bash
cd backend
php artisan serve
# Backend runs on http://localhost:8000
```

**Terminal 2 - Frontend:**
```bash
cd frontend
npm run dev
# Frontend runs on http://localhost:3000
```

### Step 7: Create First Super Admin

1. Navigate to: http://localhost:3000/create-super-admin
2. Enter the `SUPER_ADMIN_SECRET_KEY` from `backend/.env`
3. Fill in super admin details
4. Submit to create the first super admin account

---

## 🔐 User Roles & Permissions

### Super Admin
- ✅ Full access to all features
- ✅ Create, edit, delete any credential
- ✅ Create and manage admin/recruiter accounts
- ✅ Create additional super admin accounts
- ✅ Trigger email reminders and summaries
- ✅ Access User Management panel
- ✅ System configuration access

### Admin
- ✅ Full CRUD access to all credentials
- ✅ View all credentials
- ✅ Trigger email reminders and summaries
- ✅ Receive daily summary emails
- ❌ Cannot create admin/super admin accounts
- ❌ Cannot access User Management panel

### Recruiter
- ✅ View their own credentials only
- ✅ Export credentials (CSV)
- ✅ Receive reminder emails for their credentials
- ❌ Cannot create, edit, or delete credentials

---

## 📡 API Endpoints

### Base URL
```
http://localhost:8000/api
```

### Authentication Endpoints

**Register**
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "admin"
}
```

**Login**
```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123",
  "remember_me": true
}
```

**Get Authenticated User**
```http
GET /api/user
Authorization: Bearer {token}
```

**Logout**
```http
POST /api/logout
Authorization: Bearer {token}
```

**Update Profile**
```http
PUT /api/user/profile
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "name": "Updated Name",
  "email": "newemail@example.com",
  "avatar": "file" (optional)
}
```

### Credential Endpoints

**List Credentials**
```http
GET /api/credentials?page=1&per_page=10&name=keyword&type=License
Authorization: Bearer {token}
```

**Get Single Credential**
```http
GET /api/credentials/{id}
Authorization: Bearer {token}
```

**Create Credential** (Admin Only)
```http
POST /api/credentials
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
  "candidate_name": "Jane Smith",
  "position": "Software Engineer",
  "credential_type": "AWS Certification",
  "email": "jane@example.com",
  "issue_date": "2024-01-15",
  "expiry_date": "2025-01-15",
  "province": "Ontario",
  "specialty": "IT",
  "document": "file" (optional)
}
```

**Update Credential** (Admin Only)
```http
PUT /api/credentials/{id}
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Delete Credential** (Admin Only)
```http
DELETE /api/credentials/{id}
Authorization: Bearer {token}
```

**Export Credentials**
```http
GET /api/credentials/export?format=csv
Authorization: Bearer {token}
```

### Analytics Endpoints

**Dashboard Statistics**
```http
GET /api/analytics/dashboard
Authorization: Bearer {token}
```

**Status Distribution**
```http
GET /api/analytics/status-distribution
Authorization: Bearer {token}
```

**Credentials by Type**
```http
GET /api/analytics/credentials-by-type
Authorization: Bearer {token}
```

### Super Admin Endpoints

**Create Super Admin**
```http
POST /api/super-admin/create
Content-Type: application/json

{
  "secret_key": "your-secret-key",
  "name": "Super Admin",
  "email": "admin@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

---

## 🗄️ Database Schema

### Users Table
- `id` (bigint, primary key)
- `name` (string)
- `email` (string, unique)
- `email_verified_at` (timestamp, nullable)
- `password` (string, hashed)
- `role` (enum: 'super_admin', 'admin', 'recruiter', 'candidate')
- `avatar_path` (string, nullable)
- `remember_token` (string, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

### Credentials Table
- `id` (bigint, primary key)
- `candidate_name` (string)
- `position` (string)
- `credential_type` (string)
- `email` (string)
- `issue_date` (date)
- `expiry_date` (date)
- `status` (enum: 'active', 'expiring_soon', 'expired')
- `document_path` (string, nullable)
- `province` (string, nullable)
- `specialty` (string, nullable)
- `user_id` (bigint, foreign key)
- `created_at` (timestamp)
- `updated_at` (timestamp)

### Additional Tables
- `background_checks`
- `health_records`
- `work_authorizations`
- `references`
- `training_records`
- `performance_records`
- `document_verifications`
- `activity_logs`
- `saved_filters`
- `templates`
- `user_settings`

---

## 🔒 Security Features

### Authentication Security
- ✅ Bcrypt password hashing
- ✅ Token expiration (24h/30d with Remember Me)
- ✅ Real-time token validation
- ✅ Secure password reset
- ✅ Role-based access control

### Input Security
- ✅ Form request validation
- ✅ HTML tag stripping
- ✅ Special character encoding
- ✅ SQL injection protection (Eloquent ORM)
- ✅ LIKE query escaping
- ✅ XSS protection

### Response Security
- ✅ Security headers middleware
- ✅ Content Security Policy
- ✅ HTTPS enforcement
- ✅ Production-safe error handling

### API Security
- ✅ Rate limiting (all routes)
- ✅ CORS configuration
- ✅ Token-based authentication

**Rate Limits:**
- Auth routes: 5 requests/minute
- Password reset: 3 requests/minute
- Authenticated routes: 60 requests/minute
- Email routes: 10 requests/minute

---

## 📧 Email System

### Automated Reminders
- **30 days before expiry**: First reminder
- **14 days before expiry**: Second reminder
- **7 days before expiry**: Final reminder

### Daily Summary
- Sent to all administrators
- Includes all expiring credentials
- Summary of status distribution

### Email Templates
- Located in: `backend/resources/views/emails/`
- Uses Laravel Blade templates
- Markdown support
- Responsive design

### Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Goodwill Staffing"
```

---

## 🚢 Deployment

### Production Deployment Options

#### Option 1: Shared Hosting
See `SHARED_HOSTING_DEPLOYMENT.md` for detailed instructions.

**Quick Steps:**
1. Upload files via File Manager or FTP
2. Configure `.env` file
3. Set up database
4. Run migrations
5. Configure `.htaccess` files

#### Option 2: VPS/Cloud Server
See `DEPLOYMENT_GUIDE.md` for comprehensive instructions.

**Quick Steps:**
1. Set up web server (Apache/Nginx)
2. Install PHP 8.2+ and Composer
3. Install Node.js and npm
4. Clone repository
5. Configure environment
6. Build frontend: `npm run build`
7. Set up cron jobs for scheduled tasks

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up SSL certificate (HTTPS)
- [ ] Configure production email (SMTP)
- [ ] Set up cron job for scheduler
- [ ] Configure storage permissions
- [ ] Set up backup system
- [ ] Configure rate limiting
- [ ] Test all features

### Scheduled Tasks
Add to crontab:
```bash
* * * * * cd /path-to-project/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📱 Mobile Access

### Local Network Access
1. Find your local IP address
2. Update `backend/.env`: `APP_URL=http://YOUR_IP:8000`
3. Update `frontend/.env`: `VITE_API_BASE_URL=http://YOUR_IP:8000/api`
4. Start backend: `php artisan serve --host=0.0.0.0 --port=8000`
5. Access from mobile: `http://YOUR_IP:3000`

### Automated Script
**Windows:**
```bash
start-mobile.bat
```

**PowerShell:**
```powershell
.\start-mobile-dev.ps1
```

---

## 🧪 Testing

### Backend Tests
```bash
cd backend
php artisan test
```

### Frontend Tests
```bash
cd frontend
npm test
```

---

## 📚 Documentation Files

- `README.md` - Main documentation
- `COMPREHENSIVE_APP_BREAKDOWN.md` - Detailed application breakdown
- `DEPLOYMENT_GUIDE.md` - Deployment instructions
- `SECURITY_AUDIT.md` - Security documentation
- `MOBILE_ACCESS_SETUP.md` - Mobile access configuration
- `SUPER_ADMIN_CREATION.md` - Super admin setup guide
- `SHARED_HOSTING_DEPLOYMENT.md` - Shared hosting guide

---

## 🐛 Troubleshooting

### Common Issues

**1. Database Connection Error**
- Check `.env` database configuration
- Ensure database exists
- Verify credentials

**2. Token Authentication Fails**
- Check `APP_KEY` is set
- Verify token expiration
- Check CORS configuration

**3. File Upload Fails**
- Check storage permissions
- Verify `storage:link` is created
- Check file size limits

**4. Email Not Sending**
- Verify SMTP credentials
- Check email configuration in `.env`
- Test with Mailtrap first

**5. Frontend Can't Connect to Backend**
- Verify `VITE_API_BASE_URL` in frontend `.env`
- Check backend is running
- Verify CORS settings

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License.

---

## 👥 Author

**Jim-devENG** - [GitHub](https://github.com/Jim-devENG)

---

## 🔗 Repository Links

- **GitHub**: https://github.com/Jim-devENG/goodwillstaffing
- **Clone URL**: https://github.com/Jim-devENG/goodwillstaffing.git
- **Download ZIP**: https://github.com/Jim-devENG/goodwillstaffing/archive/refs/heads/main.zip

---

## 📞 Support

For support, please open an issue in the [GitHub repository](https://github.com/Jim-devENG/goodwillstaffing/issues).

---

## 🎉 Quick Start Summary

```bash
# 1. Clone repository
git clone https://github.com/Jim-devENG/goodwillstaffing.git
cd goodwillstaffing

# 2. Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link

# 3. Frontend setup
cd ../frontend
npm install
cp .env.example .env

# 4. Configure .env files (see above)

# 5. Run application
# Terminal 1:
cd backend && php artisan serve

# Terminal 2:
cd frontend && npm run dev

# 6. Access application
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000/api
```

---

**Made with ❤️ for efficient credential management**

**Production Ready | Secure | Modern | Scalable**

