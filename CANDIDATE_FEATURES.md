# Candidate Features Implementation

## Overview

The application now supports a **Candidate** role, allowing candidates to register themselves and manage their own credentials.

## Features

### 1. Candidate Registration
- **Route**: `/candidate/register`
- **Access**: Public (no authentication required)
- **Features**:
  - Candidates can register with their name, email, and password
  - Automatic role assignment as `candidate`
  - Auto-login after successful registration
  - Redirects to "My Credentials" page after registration

### 2. My Credentials Page
- **Route**: `/credentials/my-credentials`
- **Access**: Candidates only (visible in sidebar)
- **Features**:
  - View all credentials associated with candidate's email
  - Create new credentials (email auto-filled)
  - Edit existing credentials
  - Delete credentials
  - Export credentials to CSV
  - Statistics dashboard (Active, Expiring Soon, Expired, Total)
  - Days until expiry calculation

### 3. Credential Management Tracker
- **Route**: `/credentials/tracker`
- **Access**: Admins and Super Admins only
- **Features**:
  - View all credentials in the system
  - Filter by status (All, Active, Expiring Soon, Expired, Pending)
  - Statistics cards showing counts
  - Export to CSV
  - Days until expiry for each credential
  - Comprehensive credential information display

### 4. Sidebar Navigation
- **Profile**: Visible to all authenticated users (moved from topbar)
- **Credential Management**: Visible to Admins/Super Admins
- **My Credentials**: Visible to Candidates only
- **User Management**: Visible to Super Admins only

## Role Permissions

### Candidate Permissions
- ✅ Register themselves
- ✅ View their own credentials (filtered by email)
- ✅ Create credentials (must use their own email)
- ✅ Update their own credentials
- ✅ Delete their own credentials
- ❌ Cannot change email on existing credentials
- ❌ Cannot view other candidates' credentials
- ❌ Cannot access admin features

### Backend API Permissions

**GET /api/credentials**:
- Candidates: Only see credentials where `email` matches their email

**POST /api/credentials**:
- Candidates: Can create credentials, but email must match their own

**PUT /api/credentials/{id}**:
- Candidates: Can only update credentials where `email` matches their email
- Candidates: Cannot change the email field

**DELETE /api/credentials/{id}**:
- Candidates: Can only delete credentials where `email` matches their email

## Database Migration

The `candidate` role has been added to the `users` table enum:

```php
$table->enum('role', ['super_admin', 'admin', 'recruiter', 'candidate'])
```

### For New Installations
Run: `php artisan migrate:fresh`

### For Existing Installations
Since SQLite doesn't support altering enum columns, you have two options:

1. **Reset Database** (recommended for development):
   ```bash
   php artisan migrate:fresh
   ```

2. **Manual Update** (for production):
   - Export existing data
   - Drop and recreate the users table with the new enum
   - Re-import data

## User Flow

### Candidate Registration Flow
1. Visit `/candidate/register` or click "Register as Candidate" on login page
2. Fill in name, email, and password
3. Submit form
4. Auto-login after successful registration
5. Redirected to `/credentials/my-credentials`

### Candidate Credential Management Flow
1. Login as candidate
2. Navigate to "My Credentials" from sidebar
3. View all credentials associated with their email
4. Add new credentials (email pre-filled)
5. Edit or delete existing credentials
6. Export credentials to CSV

### Admin Credential Tracking Flow
1. Login as admin/super admin
2. Navigate to "Credential Management" from sidebar
3. View all credentials in the system
4. Filter by status
5. Export to CSV
6. Monitor expiring credentials

## Frontend Components

### New Pages
- `frontend/src/pages/CandidateRegistration.jsx`
- `frontend/src/pages/MyCredentials.jsx`
- `frontend/src/pages/CredentialTracker.jsx`

### Updated Components
- `frontend/src/components/Layout/Sidebar.jsx` - Added new menu items
- `frontend/src/components/Layout/Topbar.jsx` - Removed profile button, shows user info
- `frontend/src/components/CredentialForm.jsx` - Added defaultEmail and defaultCandidateName props
- `frontend/src/pages/Login.jsx` - Added candidate registration link

## Backend Updates

### Controllers
- `backend/app/Http/Controllers/Api/AuthController.php` - Added candidate role support
- `backend/app/Http/Controllers/Api/CredentialController.php` - Added candidate permission checks

### Routes
- `backend/routes/api.php` - Updated credential routes to allow candidate access

### Migrations
- `backend/database/migrations/2025_11_07_220008_add_role_to_users_table.php` - Updated enum to include 'candidate'

## Testing Checklist

- [ ] Candidate can register at `/candidate/register`
- [ ] Candidate is auto-logged in after registration
- [ ] Candidate is redirected to "My Credentials" page
- [ ] Candidate can see "My Credentials" in sidebar
- [ ] Candidate can create credentials with their email
- [ ] Candidate cannot create credentials with different email
- [ ] Candidate can view only their own credentials
- [ ] Candidate can edit their own credentials
- [ ] Candidate cannot change email on credentials
- [ ] Candidate can delete their own credentials
- [ ] Admin can see "Credential Management" in sidebar
- [ ] Admin can view all credentials in tracker
- [ ] Admin can filter credentials by status
- [ ] Profile link appears in sidebar for all users
- [ ] Login page shows "Register as Candidate" link

## Security Notes

1. **Email Validation**: Candidates can only manage credentials with their registered email
2. **Role Enforcement**: Backend validates role and email on every credential operation
3. **SQL Injection Protection**: All queries use parameterized statements
4. **Input Sanitization**: All user inputs are sanitized before database operations

