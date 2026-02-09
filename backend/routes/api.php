<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint (for Render to prevent sleep)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
    ]);
});

// Public authentication routes with rate limiting
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->middleware('throttle:5,1'); // 5 requests per minute
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 requests per minute
Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword'])->middleware('throttle:3,1'); // 3 requests per minute
Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->middleware('throttle:3,1'); // 3 requests per minute

// Platform admin creation (special endpoint)
// - If no platform admin exists: requires secret key (public endpoint)
// - If platform admin exists: requires platform admin authentication (protected)
Route::post('/super-admin/create', [\App\Http\Controllers\Api\SuperAdminController::class, 'createSuperAdmin'])->middleware('throttle:3,1'); // 3 requests per minute

// Protected routes with rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () { // 60 requests per minute for authenticated users
    // Authentication routes
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::put('/user/profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::put('/user/password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);
    
    // User Settings routes
    Route::get('/settings', [\App\Http\Controllers\Api\SettingsController::class, 'index']);
    Route::put('/settings', [\App\Http\Controllers\Api\SettingsController::class, 'update']);
    Route::post('/settings/reset', [\App\Http\Controllers\Api\SettingsController::class, 'reset']);
    
    // Credentials routes with role-based access
    // Admin/Super Admin: full access (create, update, delete)
    // Recruiter: view and export only
    // Candidate: can view and manage their own credentials (filtered by email)
    Route::get('/credentials', [\App\Http\Controllers\Api\CredentialController::class, 'index']);
    Route::get('/credentials/{id}', [\App\Http\Controllers\Api\CredentialController::class, 'show']);
    
    // Credential management routes
    // Admin/Super Admin: can manage all credentials
    // Candidate: can manage their own credentials (checked in controller)
    Route::post('/credentials', [\App\Http\Controllers\Api\CredentialController::class, 'store']);
    Route::put('/credentials/{id}', [\App\Http\Controllers\Api\CredentialController::class, 'update']);
    Route::delete('/credentials/{id}', [\App\Http\Controllers\Api\CredentialController::class, 'destroy']);
    
    // HR Features - Background Checks
    Route::get('/background-checks', [\App\Http\Controllers\Api\BackgroundCheckController::class, 'index']);
    Route::get('/background-checks/{id}', [\App\Http\Controllers\Api\BackgroundCheckController::class, 'show']);
    Route::post('/background-checks', [\App\Http\Controllers\Api\BackgroundCheckController::class, 'store']);
    Route::put('/background-checks/{id}', [\App\Http\Controllers\Api\BackgroundCheckController::class, 'update']);
    Route::delete('/background-checks/{id}', [\App\Http\Controllers\Api\BackgroundCheckController::class, 'destroy']);
    
    // HR Features - Health Records
    Route::get('/health-records', [\App\Http\Controllers\Api\HealthRecordController::class, 'index']);
    Route::get('/health-records/{id}', [\App\Http\Controllers\Api\HealthRecordController::class, 'show']);
    Route::post('/health-records', [\App\Http\Controllers\Api\HealthRecordController::class, 'store']);
    Route::put('/health-records/{id}', [\App\Http\Controllers\Api\HealthRecordController::class, 'update']);
    Route::delete('/health-records/{id}', [\App\Http\Controllers\Api\HealthRecordController::class, 'destroy']);
    
    // HR Features - Work Authorizations
    Route::get('/work-authorizations', [\App\Http\Controllers\Api\WorkAuthorizationController::class, 'index']);
    Route::get('/work-authorizations/{id}', [\App\Http\Controllers\Api\WorkAuthorizationController::class, 'show']);
    Route::post('/work-authorizations', [\App\Http\Controllers\Api\WorkAuthorizationController::class, 'store']);
    Route::put('/work-authorizations/{id}', [\App\Http\Controllers\Api\WorkAuthorizationController::class, 'update']);
    Route::delete('/work-authorizations/{id}', [\App\Http\Controllers\Api\WorkAuthorizationController::class, 'destroy']);
    
    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Api\AnalyticsController::class, 'index']);
    
    // Activity Log
    Route::get('/activity-logs', [\App\Http\Controllers\Api\ActivityLogController::class, 'index']);
    
    // Templates
    Route::get('/templates', [\App\Http\Controllers\Api\TemplateController::class, 'index']);
    Route::post('/templates', [\App\Http\Controllers\Api\TemplateController::class, 'store']);
    Route::put('/templates/{id}', [\App\Http\Controllers\Api\TemplateController::class, 'update']);
    Route::delete('/templates/{id}', [\App\Http\Controllers\Api\TemplateController::class, 'destroy']);
    
    // Saved Filters
    Route::get('/filters', [\App\Http\Controllers\Api\FilterController::class, 'index']);
    Route::post('/filters', [\App\Http\Controllers\Api\FilterController::class, 'store']);
    Route::put('/filters/{id}', [\App\Http\Controllers\Api\FilterController::class, 'update']);
    Route::delete('/filters/{id}', [\App\Http\Controllers\Api\FilterController::class, 'destroy']);
    
    // Import/Export
    Route::post('/import', [\App\Http\Controllers\Api\ImportExportController::class, 'import']);

    // Platform admin (landlord) provisioning
    Route::middleware('role.super_admin')->prefix('platform')->group(function () {
        Route::get('/organizations', [\App\Http\Controllers\Api\PlatformOrganizationController::class, 'index']);
        Route::post('/organizations', [\App\Http\Controllers\Api\PlatformOrganizationController::class, 'store']);
        Route::post('/organizations/{organization}/domains', [\App\Http\Controllers\Api\PlatformOrganizationController::class, 'addDomain']);
        Route::post('/organizations/{organization}/owner', [\App\Http\Controllers\Api\PlatformOrganizationController::class, 'createOwner']);
    });
    
    // Email triggers remain admin-only
    Route::middleware('role.admin')->group(function () {
        // Email trigger routes with stricter rate limiting
        Route::post('/emails/send-reminders', [\App\Http\Controllers\Api\EmailController::class, 'sendReminders'])->middleware('throttle:10,1'); // 10 requests per minute
        Route::post('/emails/send-summary', [\App\Http\Controllers\Api\EmailController::class, 'sendSummary'])->middleware('throttle:10,1'); // 10 requests per minute
    });

    // Admin + org_super_admin routes
    Route::middleware('role.recruiter')->group(function () {
        // Email Settings (admin/org_super_admin)
        Route::get('/email-settings', [\App\Http\Controllers\Api\EmailSettingsController::class, 'index']);
        Route::put('/email-settings', [\App\Http\Controllers\Api\EmailSettingsController::class, 'update']);
        Route::post('/email-settings/test', [\App\Http\Controllers\Api\EmailSettingsController::class, 'test']);

        // User management routes
        // - platform_admin: can manage users across organizations
        // - org_super_admin: can manage users within their organization
        Route::get('/admin/users', [\App\Http\Controllers\Api\AdminController::class, 'getUsers']);
        Route::post('/admin/users', [\App\Http\Controllers\Api\AdminController::class, 'createUser']);
        Route::put('/admin/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateUser']);
        Route::delete('/admin/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
    });
});

