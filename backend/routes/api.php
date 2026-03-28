<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// MINIMAL TEST: Bypass all middleware - direct response
Route::get('/test-minimal', function () {
    return response()->json(['test' => 'minimal', 'time' => microtime(true)])
        ->header('X-Test', 'minimal');
});

// TEST: Check organizations in database (bypass tenant scope)
Route::get('/test-orgs', function () {
    $orgs = \App\Models\Organization::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
        ->select('id', 'name', 'slug', 'subdomain', 'is_active')
        ->get();
    return response()->json(['count' => $orgs->count(), 'organizations' => $orgs]);
});

// TEST: Send test email via SES (bypass tenant scope)
Route::post('/test-email', function (\Illuminate\Http\Request $request) {
    $email = $request->input('email', 'jimmy@blueinctech.com');
    try {
        \Illuminate\Support\Facades\Mail::raw('Test email from AgencyHQ at ' . now(), function ($message) use ($email) {
            $message->to($email)->subject('Test Email from AgencyHQ');
        });
        return response()->json(['success' => true, 'message' => 'Email sent to ' . $email]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

// HEALTH CHECK: Must NOT require database connection
// This endpoint is used by ELB to verify container health
// Session middleware would try to connect to DB, so we disable it
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
    ])->header('X-Health-Check', 'true');
})->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class]);

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

// Public authentication routes with rate limiting
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->middleware('throttle:5,1'); // 5 requests per minute
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 requests per minute
Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword'])->middleware('throttle:3,1'); // 3 requests per minute
Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword'])->middleware('throttle:3,1'); // 3 requests per minute

// Platform admin creation (special endpoint)
// - If no platform admin exists: requires secret key (public endpoint)
// - If platform admin exists: requires platform admin authentication (protected)
Route::post('/super-admin/create', [\App\Http\Controllers\Api\SuperAdminController::class, 'createSuperAdmin'])->middleware('throttle:3,1'); // 3 requests per minute

Route::post('/super-admin/set-password', [\App\Http\Controllers\Api\SuperAdminController::class, 'setPlatformAdminPassword'])->middleware('throttle:3,1'); // 3 requests per minute

Route::prefix('private/platform-admin')->middleware('throttle:3,1')->group(function () {
    Route::post('/upsert', [\App\Http\Controllers\Api\SuperAdminController::class, 'privateUpsertPlatformAdmin']);
    Route::post('/reset-first-password', [\App\Http\Controllers\Api\SuperAdminController::class, 'privateResetFirstPlatformAdminPassword']);
    Route::post('/test-email', [\App\Http\Controllers\Api\SuperAdminController::class, 'privateSendTestEmail']);
});

// Candidate Portal (passwordless)
Route::prefix('v1/portal')->middleware('throttle:10,1')->group(function () {
    Route::post('/request-code', [\App\Http\Controllers\Api\PortalAuthController::class, 'requestCode']);
    Route::post('/verify-code', [\App\Http\Controllers\Api\PortalAuthController::class, 'verifyCode']);
});

// Public submission viewer (unauthenticated)
Route::get('/public/submission/{token}', [\App\Http\Controllers\PublicSubmissionController::class, 'show'])->middleware('throttle:30,1');

// Public candidate registration (Phase 1 - Basic Intake)
Route::post('/public/{org_slug}/register', [\App\Http\Controllers\Api\PublicCandidateController::class, 'register'])->middleware('throttle:5,1');
Route::post('/public/{org_slug}/candidate-status', [\App\Http\Controllers\Api\PublicCandidateController::class, 'status'])->middleware('throttle:5,1');

// Public organization signup + onboarding helpers (unauthenticated)
Route::prefix('public')->middleware('throttle:10,1')->group(function () {
    Route::get('/job-board', [\App\Http\Controllers\Api\PublicJobBoardController::class, 'index']);
    Route::get('/job-board/{id}', [\App\Http\Controllers\Api\PublicJobBoardController::class, 'show']);
    Route::post('/job-board/{id}/apply', [\App\Http\Controllers\Api\PublicJobBoardController::class, 'apply']);
    Route::post('/organizations/signup', [\App\Http\Controllers\Api\PublicOrganizationSignupController::class, 'signup']);
    Route::get('/subdomain/check', [\App\Http\Controllers\Api\TenantOnboardingController::class, 'checkSubdomain']);
});

// Tenant branding (public read-only; used by SPA during initial boot)
Route::get('/brand', [\App\Http\Controllers\Api\BrandController::class, 'show']);
Route::get('/brand/logo/{organization}', [\App\Http\Controllers\Api\BrandController::class, 'logo']);

// Protected routes with rate limiting
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () { // 60 requests per minute for authenticated users
    // Authentication routes
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::put('/user/profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::put('/user/password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);

    // User presence & messaging
    Route::get('/users/online', [\App\Http\Controllers\Api\UserPresenceController::class, 'online']);
    Route::post('/users/heartbeat', [\App\Http\Controllers\Api\UserPresenceController::class, 'heartbeat']);
    Route::get('/messages/unread-count', [\App\Http\Controllers\Api\UserPresenceController::class, 'unreadCount']);

    // Jobs CRUD (scoped to user's organization)
    Route::get('/jobs', [\App\Http\Controllers\Api\JobController::class, 'index']);
    Route::post('/jobs', [\App\Http\Controllers\Api\JobController::class, 'store']);
    Route::get('/jobs/{id}', [\App\Http\Controllers\Api\JobController::class, 'show']);
    Route::put('/jobs/{id}', [\App\Http\Controllers\Api\JobController::class, 'update']);
    Route::delete('/jobs/{id}', [\App\Http\Controllers\Api\JobController::class, 'destroy']);

    // Candidate job board - only shows jobs from candidate's organization
    Route::get('/my-org/jobs', [\App\Http\Controllers\Api\CandidateJobController::class, 'index']);
    Route::get('/my-org/jobs/{id}', [\App\Http\Controllers\Api\CandidateJobController::class, 'show']);

    // External intake gateway
    Route::prefix('v1')->group(function () {
        Route::post('/intake/candidate', [\App\Http\Controllers\Api\IntakeController::class, 'store']);
        Route::get('/intake/recent', [\App\Http\Controllers\Api\IntakeController::class, 'recent']);
        Route::post('/intake/admin', [\App\Http\Controllers\Api\IntakeController::class, 'adminStore'])->middleware('role.recruiter');
        Route::get('/intake/tokens', [\App\Http\Controllers\Api\IntakeTokenController::class, 'index'])->middleware('role.recruiter');
        Route::post('/intake/tokens', [\App\Http\Controllers\Api\IntakeTokenController::class, 'store'])->middleware('role.recruiter');
        Route::delete('/intake/tokens/{id}', [\App\Http\Controllers\Api\IntakeTokenController::class, 'destroy'])->middleware('role.recruiter');

        Route::prefix('portal')->middleware('role.candidate')->group(function () {
            Route::get('/me', [\App\Http\Controllers\Api\PortalAuthController::class, 'me']);
            Route::get('/profile', [\App\Http\Controllers\Api\PortalProfileController::class, 'show']);
            Route::put('/profile', [\App\Http\Controllers\Api\PortalProfileController::class, 'update']);
            Route::post('/profile/upload', [\App\Http\Controllers\Api\PortalProfileController::class, 'upload']);
            Route::get('/credentials', [\App\Http\Controllers\Api\PortalCredentialController::class, 'index']);
            Route::get('/requirements', [\App\Http\Controllers\Api\PortalRequirementsController::class, 'index']);
            Route::post('/credentials', [\App\Http\Controllers\Api\PortalCredentialController::class, 'store']);
            Route::post('/credentials/{id}/upload', [\App\Http\Controllers\Api\PortalCredentialController::class, 'upload']);
            Route::get('/jobs', [\App\Http\Controllers\Api\PortalJobsController::class, 'index']);
            Route::get('/jobs/{id}', [\App\Http\Controllers\Api\PortalJobsController::class, 'show']);
            Route::get('/my-travel', [\App\Http\Controllers\Api\PortalPlacementController::class, 'myTravel']);
            Route::post('/placements/{id}/confirm-arrival', [\App\Http\Controllers\Api\PortalPlacementController::class, 'confirmArrival']);

            Route::get('/availability', [\App\Http\Controllers\Api\PortalAvailabilityController::class, 'index']);
            Route::post('/availability', [\App\Http\Controllers\Api\PortalAvailabilityController::class, 'store']);
            Route::put('/availability/{id}', [\App\Http\Controllers\Api\PortalAvailabilityController::class, 'update']);
            Route::delete('/availability/{id}', [\App\Http\Controllers\Api\PortalAvailabilityController::class, 'destroy']);
        });

        // New Candidate Portal routes
        Route::prefix('candidate')->middleware('role.candidate')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\CandidatePortalController::class, 'dashboard']);
            Route::get('/me', [\App\Http\Controllers\Api\CandidatePortalController::class, 'me']);
            Route::get('/applications', [\App\Http\Controllers\Api\CandidatePortalController::class, 'applications']);
            Route::get('/placements', [\App\Http\Controllers\Api\CandidatePortalController::class, 'placements']);
            Route::get('/timesheets', [\App\Http\Controllers\Api\CandidatePortalController::class, 'timesheets']);
            Route::post('/timesheets', [\App\Http\Controllers\Api\CandidatePortalController::class, 'storeTimesheet']);
            Route::get('/payments', [\App\Http\Controllers\Api\CandidatePortalController::class, 'payments']);
        });

        // Placements (candidate action)
        Route::post('/placements/express-interest/{jobOrderId}', [\App\Http\Controllers\Api\PlacementController::class, 'expressInterest'])->middleware('role.candidate');

        // Revenue Core: Job Orders + Placement Pipeline
        Route::prefix('job-orders')->middleware('role.recruiter')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\JobOrderController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\JobOrderController::class, 'show']);
            Route::post('/', [\App\Http\Controllers\Api\JobOrderController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\JobOrderController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\JobOrderController::class, 'destroy']);
        });

        Route::prefix('job-sources')->middleware('role.recruiter')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\JobSourceController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\JobSourceController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\JobSourceController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\JobSourceController::class, 'destroy']);
            Route::post('/{id}/run', [\App\Http\Controllers\Api\JobSourceController::class, 'run']);
        });

        // Outbound submissions (recruiter-only)
        Route::prefix('submissions')->middleware('role.recruiter')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\SubmissionController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\SubmissionController::class, 'store']);
            Route::get('/candidate/{candidateId}', [\App\Http\Controllers\Api\SubmissionController::class, 'history']);
            Route::post('/{id}/revoke', [\App\Http\Controllers\Api\SubmissionController::class, 'revoke']);
            Route::post('/{id}/expire', [\App\Http\Controllers\Api\SubmissionController::class, 'expire']);
        });

        // Candidates
        // - org_super_admin can view list/search/profile
        // - only recruiter can create/update/delete candidates
        Route::prefix('candidates')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CandidateController::class, 'index'])->middleware('role.candidate_view');
            Route::get('/search', [\App\Http\Controllers\Api\CandidateController::class, 'search'])->middleware('role.candidate_view');
            Route::get('/export', [\App\Http\Controllers\Api\CandidateController::class, 'export'])->middleware('role.candidate_view');
            Route::get('/{id}', [\App\Http\Controllers\Api\CandidateController::class, 'show'])->middleware('role.candidate_view');
            Route::get('/{id}/documents', [\App\Http\Controllers\Api\CandidateController::class, 'documents'])->middleware('role.candidate_view');
            Route::get('/{id}/credentials', [\App\Http\Controllers\Api\CandidateController::class, 'credentials'])->middleware('role.candidate_view');

            Route::prefix('import')->middleware('role.candidate_manage')->group(function () {
                Route::get('/template', [\App\Http\Controllers\Api\CandidateImportController::class, 'template']);
                Route::post('/parse', [\App\Http\Controllers\Api\CandidateImportController::class, 'parse']);
                Route::post('/commit', [\App\Http\Controllers\Api\CandidateImportController::class, 'commit']);
            });

            Route::post('/', [\App\Http\Controllers\Api\CandidateController::class, 'store'])->middleware('role.candidate_manage');
            Route::put('/{id}', [\App\Http\Controllers\Api\CandidateController::class, 'update'])->middleware('role.candidate_manage');
            Route::delete('/{id}', [\App\Http\Controllers\Api\CandidateController::class, 'destroy'])->middleware('role.candidate_manage');
            Route::post('/{id}/documents', [\App\Http\Controllers\Api\CandidateController::class, 'uploadDocuments'])->middleware('role.candidate_manage');
            Route::delete('/documents/{documentId}', [\App\Http\Controllers\Api\CandidateController::class, 'deleteDocument'])->middleware('role.candidate_manage');
        });

        // Candidate Pipeline (recruiter-only)
        Route::prefix('candidate-pipeline')->middleware('role.recruiter')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CandidatePipelineController::class, 'index']);
            Route::get('/{candidateId}', [\App\Http\Controllers\Api\CandidatePipelineController::class, 'show']);
            Route::put('/{candidateId}/stage', [\App\Http\Controllers\Api\CandidatePipelineController::class, 'setStage']);
            Route::put('/{candidateId}/recruiter', [\App\Http\Controllers\Api\CandidatePipelineController::class, 'assignRecruiter']);
            Route::post('/{candidateId}/notes', [\App\Http\Controllers\Api\CandidatePipelineController::class, 'addNote']);
        });

        Route::prefix('placements')->middleware('role.recruiter')->group(function () {
            Route::get('/board', [\App\Http\Controllers\Api\PlacementController::class, 'board']);
            Route::get('/{id}', [\App\Http\Controllers\Api\PlacementController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\PlacementController::class, 'update']);
            Route::put('/{id}/stage', [\App\Http\Controllers\Api\PlacementController::class, 'moveStage']);
        });

        // Shifts
        Route::prefix('shifts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ShiftController::class, 'index'])->middleware('role.scheduler');
            Route::get('/templates', [\App\Http\Controllers\Api\ShiftController::class, 'templates'])->middleware('role.scheduler');
            Route::get('/assignments/active', [\App\Http\Controllers\Api\ShiftController::class, 'activeAssignments'])->middleware('role.scheduler');
            Route::post('/availability/preview', [\App\Http\Controllers\Api\ShiftController::class, 'availabilityPreview'])->middleware('role.scheduler');
            Route::post('/availability/preview-shift', [\App\Http\Controllers\Api\ShiftController::class, 'availabilityPreviewShift'])->middleware('role.scheduler');
            Route::post('/availability/unavailable', [\App\Http\Controllers\Api\ShiftController::class, 'markCandidateUnavailable'])->middleware('role.scheduler');
            Route::get('/available', [\App\Http\Controllers\Api\ShiftController::class, 'available'])->middleware('role.candidate');
            Route::get('/{id}', [\App\Http\Controllers\Api\ShiftController::class, 'show'])->middleware('role.scheduler');
            Route::post('/', [\App\Http\Controllers\Api\ShiftController::class, 'store'])->middleware('role.scheduler');
            Route::post('/{id}/request', [\App\Http\Controllers\Api\ShiftController::class, 'requestShift'])->middleware('role.candidate');
            Route::post('/{id}/withdraw', [\App\Http\Controllers\Api\ShiftController::class, 'withdrawRequest'])->middleware('role.candidate');
            Route::post('/{id}/check-in', [\App\Http\Controllers\Api\ShiftController::class, 'checkIn'])->middleware('role.candidate');
            Route::post('/{id}/check-out', [\App\Http\Controllers\Api\ShiftController::class, 'checkOut'])->middleware('role.candidate');
            Route::post('/{id}/cancel', [\App\Http\Controllers\Api\ShiftController::class, 'cancel'])->middleware('role.scheduler');
            Route::post('/{id}/complete', [\App\Http\Controllers\Api\ShiftController::class, 'complete'])->middleware('role.scheduler');

            Route::post('/requests/{requestId}/approve', [\App\Http\Controllers\Api\ShiftController::class, 'approveRequest'])->middleware('role.admin');
            Route::post('/requests/{requestId}/reject', [\App\Http\Controllers\Api\ShiftController::class, 'rejectRequest'])->middleware('role.admin');
        });

        // Compliance readiness status (agency staff)
        Route::get('/compliance/worker/{candidateId}/status', [\App\Http\Controllers\Api\ComplianceStatusController::class, 'show'])
            ->middleware('role.candidate_view');

        // Timesheets (candidate + admin)
        Route::prefix('timesheets')->group(function () {
            // Candidate
            Route::get('/', [\App\Http\Controllers\Api\TimesheetController::class, 'index'])->middleware('role.candidate');
            Route::post('/', [\App\Http\Controllers\Api\TimesheetController::class, 'store'])->middleware('role.candidate');
            Route::post('/{id}/submit', [\App\Http\Controllers\Api\TimesheetController::class, 'submit'])->middleware('role.candidate');

            // Admin
            Route::get('/pending', [\App\Http\Controllers\Api\TimesheetController::class, 'pending'])->middleware('role.admin');
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\TimesheetController::class, 'approve'])->middleware('role.admin');
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\TimesheetController::class, 'reject'])->middleware('role.admin');
        });

        // Revenue analytics (timesheet-based)
        Route::prefix('revenue')->middleware('role.finance')->group(function () {
            Route::get('/analytics', [\App\Http\Controllers\Api\RevenueController::class, 'analytics']);
        });

        // Billing & AR analytics
        Route::prefix('billing')->middleware('role.finance')->group(function () {
            Route::get('/analytics', [\App\Http\Controllers\Api\BillingAnalyticsController::class, 'index']);
        });

        // Invoices
        Route::middleware('role.finance')->get('/invoices', [\App\Http\Controllers\Api\InvoiceController::class, 'index']);
        Route::middleware('role.finance')->get('/invoices/{id}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
        Route::middleware('role.finance')->post('/invoices/{id}/issue', [\App\Http\Controllers\Api\InvoiceController::class, 'issue']);
        Route::middleware('role.finance')->post('/invoices/{id}/mark-paid', [\App\Http\Controllers\Api\InvoiceController::class, 'markPaid']);

        // Payments
        Route::middleware('role.finance')->post('/payments', [\App\Http\Controllers\Api\PaymentController::class, 'store']);

        Route::prefix('logistics')->middleware('role.recruiter')->group(function () {
            Route::get('/needs-arrival', [\App\Http\Controllers\Api\LogisticsController::class, 'needsArrival']);
            Route::get('/placements/{placementId}', [\App\Http\Controllers\Api\LogisticsController::class, 'show']);
            Route::put('/placements/{placementId}/housing', [\App\Http\Controllers\Api\LogisticsController::class, 'upsertHousing']);
            Route::post('/placements/{placementId}/travel', [\App\Http\Controllers\Api\LogisticsController::class, 'storeTravelLog']);
            Route::put('/travel/{id}', [\App\Http\Controllers\Api\LogisticsController::class, 'updateTravelLog']);
            Route::delete('/travel/{id}', [\App\Http\Controllers\Api\LogisticsController::class, 'destroyTravelLog']);
        });

        Route::prefix('compliance-queue')->middleware('role.compliance')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ComplianceQueueController::class, 'index']);
            Route::post('/{id}/approve', [\App\Http\Controllers\Api\ComplianceQueueController::class, 'approve']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\ComplianceQueueController::class, 'reject']);
        });

        Route::prefix('compliance')->middleware('role.compliance')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\ComplianceDashboardController::class, 'index']);
        });

        // Finance (agency admin only)
        Route::prefix('finance')->middleware('role.finance')->group(function () {
            Route::get('/summary', [\App\Http\Controllers\Api\FinanceController::class, 'summary']);
        });

        // Agency branding (agency admin only)
        Route::prefix('agency')->middleware('role.org_owner')->group(function () {
            Route::put('/branding', [\App\Http\Controllers\Api\AgencyBrandingController::class, 'update']);
        });

        // Tenant onboarding (org_super_admin)
        Route::prefix('onboarding')->middleware('role.org_owner')->group(function () {
            Route::get('/status', [\App\Http\Controllers\Api\TenantOnboardingController::class, 'status']);
            Route::post('/subdomain', [\App\Http\Controllers\Api\TenantOnboardingController::class, 'setSubdomain']);
            Route::post('/branding', [\App\Http\Controllers\Api\TenantOnboardingController::class, 'branding']);
            Route::post('/complete', [\App\Http\Controllers\Api\TenantOnboardingController::class, 'complete']);
        });

        // Facilities management (org_super_admin)
        Route::prefix('facilities')->middleware('role.org_owner')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\FacilityManagementController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\FacilityManagementController::class, 'store']);
            
            // Facility detail workspace (must come before {facility}/users to avoid route conflict)
            Route::get('/{id}', [\App\Http\Controllers\Api\FacilityController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/{id}/contracts', [\App\Http\Controllers\Api\FacilityController::class, 'contracts'])->where('id', '[0-9]+');
            Route::get('/{id}/billing', [\App\Http\Controllers\Api\FacilityController::class, 'billing'])->where('id', '[0-9]+');
            
            Route::post('/{facility}/users', [\App\Http\Controllers\Api\FacilityManagementController::class, 'createFacilityUser']);
            
            // Contracts (facility-scoped)
            Route::prefix('{facilityId}/contracts')->where(['facilityId' => '[0-9]+'])->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\ContractController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Api\ContractController::class, 'store']);
                Route::get('/{id}', [\App\Http\Controllers\Api\ContractController::class, 'show']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\ContractController::class, 'destroy']);
                Route::post('/{id}/extract', [\App\Http\Controllers\Api\ContractController::class, 'extract']);
                Route::get('/{id}/extracted-terms', [\App\Http\Controllers\Api\ContractController::class, 'extractedTerms']);
                Route::post('/{id}/review', [\App\Http\Controllers\Api\ContractController::class, 'review']);
            });
            
            // Billing settings (facility-scoped)
            Route::prefix('{facilityId}/billing')->where(['facilityId' => '[0-9]+'])->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\BillingController::class, 'show']);
                Route::put('/', [\App\Http\Controllers\Api\BillingController::class, 'update']);
                Route::post('/apply-contract/{contractId}', [\App\Http\Controllers\Api\BillingController::class, 'applyContract']);
                Route::get('/preview-contract/{contractId}', [\App\Http\Controllers\Api\BillingController::class, 'previewContract']);
            });
        });

        // Platform admin: global health + broadcast
        Route::prefix('admin')->middleware('role.super_admin')->group(function () {
            Route::get('/platform-health', [\App\Http\Controllers\Api\PlatformAdminController::class, 'platformHealth']);
            Route::post('/system-message', [\App\Http\Controllers\Api\SystemMessageController::class, 'upsert']);
            Route::post('/system-message/clear', [\App\Http\Controllers\Api\SystemMessageController::class, 'clear']);
        });

        // Facility Portal
        Route::prefix('facility')->middleware('role.facility')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\FacilityPortalController::class, 'dashboard']);
            Route::get('/workers', [\App\Http\Controllers\Api\FacilityPortalController::class, 'workers']);
            Route::get('/shifts', [\App\Http\Controllers\Api\FacilityPortalController::class, 'shifts']);
            Route::prefix('timesheets')->group(function () {
                Route::get('/pending', [\App\Http\Controllers\Api\FacilityPortalController::class, 'pendingTimesheets']);
                Route::post('/{id}/approve', [\App\Http\Controllers\Api\FacilityPortalController::class, 'approveTimesheet']);
                Route::post('/{id}/reject', [\App\Http\Controllers\Api\FacilityPortalController::class, 'rejectTimesheet']);
            });
            Route::prefix('invoices')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\FacilityPortalController::class, 'invoices']);
                Route::get('/{id}', [\App\Http\Controllers\Api\FacilityPortalController::class, 'invoiceShow']);
            });
        });

        // Global system banner (all authenticated roles)
        Route::get('/system/banner', [\App\Http\Controllers\Api\SystemMessageController::class, 'banner']);
    });
    
    // User Settings routes
    Route::get('/settings', [\App\Http\Controllers\Api\SettingsController::class, 'index']);
});

Route::get('/credentials/documents/{path}', [\App\Http\Controllers\Api\CredentialDocumentController::class, 'show'])
    ->where('path', '.*')
    ->name('credentials.documents.show');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () { 
    Route::put('/settings', [\App\Http\Controllers\Api\SettingsController::class, 'update']);
    Route::post('/settings/reset', [\App\Http\Controllers\Api\SettingsController::class, 'reset']);

    // Messaging routes
    Route::get('/messages', [\App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::post('/messages', [\App\Http\Controllers\Api\MessageController::class, 'store']);

    Route::get('/candidate/me', [\App\Http\Controllers\Api\CandidatePortalController::class, 'me']);
    Route::get('/org/recruiters', [\App\Http\Controllers\Api\OrgController::class, 'recruiters']);
    Route::get('/org/candidate-users', [\App\Http\Controllers\Api\OrgController::class, 'candidateUsers']);
    Route::get('/org/staff-chat-users', [\App\Http\Controllers\Api\OrgController::class, 'staffUsersForCandidateChat']);

    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);

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
    Route::get('/analytics/revenue', [\App\Http\Controllers\Api\AnalyticsController::class, 'revenue']);
    Route::get('/analytics/facility-performance', [\App\Http\Controllers\Api\AnalyticsController::class, 'facilityPerformance']);
    Route::get('/analytics/recruiter-performance', [\App\Http\Controllers\Api\AnalyticsController::class, 'recruiterPerformance']);
    Route::get('/analytics/job-fill-time', [\App\Http\Controllers\Api\AnalyticsController::class, 'jobFillTime']);
    
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
    Route::middleware('role.org_owner')->group(function () {
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

