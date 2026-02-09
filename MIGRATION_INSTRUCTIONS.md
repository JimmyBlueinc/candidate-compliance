# Migration Instructions for HR Features

## Important: Run Migrations First!

The new HR features (Background Checks, Health Records, Work Authorizations) require database migrations to be run before they will work.

## Steps to Run Migrations

1. **Navigate to the backend directory:**
   ```bash
   cd backend
   ```

2. **Run the migrations:**
   ```bash
   php artisan migrate
   ```

   This will create the following new tables:
   - `background_checks`
   - `health_records`
   - `work_authorizations`
   - `references`
   - `training_records`
   - `performance_records`
   - `document_verifications`

3. **Verify migrations ran successfully:**
   ```bash
   php artisan migrate:status
   ```

   You should see all migrations listed as "Ran".

## Troubleshooting

If you get errors:

1. **"Table already exists"** - This means the migrations were already run. You can skip this step.

2. **"Class not found"** - Make sure you're in the `backend` directory and have run `composer install`.

3. **"SQLSTATE" errors** - Check your database connection in `.env` file.

## After Running Migrations

Once migrations are complete, the API endpoints will work:
- `/api/background-checks`
- `/api/health-records`
- `/api/work-authorizations`

The frontend pages should now load without 500 errors.

