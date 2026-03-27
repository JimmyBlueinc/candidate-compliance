<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Reminder emails (30, 14, 7 days) - daily at 09:00
        $schedule->command('credentials:send-reminders')
            ->dailyAt('09:00')
            ->description('Send credential expiry reminders (30, 14, 7 days)');

        // Daily summary to Admin - daily at 08:00
        $schedule->command('credentials:send-summary')
            ->dailyAt('08:00')
            ->description('Send daily credential expiry summary to Admin');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom middleware aliases
        $middleware->alias([
            'org.resolve' => \App\Http\Middleware\ResolveOrganization::class,
            'role.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'role.super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'role.recruiter' => \App\Http\Middleware\EnsureUserIsRecruiter::class,
            'role.scheduler' => \App\Http\Middleware\EnsureUserIsScheduler::class,
            'role.org_owner' => \App\Http\Middleware\EnsureUserIsOrgOwner::class,
            'role.compliance' => \App\Http\Middleware\EnsureUserIsCompliance::class,
            'role.finance' => \App\Http\Middleware\EnsureUserIsFinance::class,
            'role.travel' => \App\Http\Middleware\EnsureUserIsTravel::class,
            'role.facility' => \App\Http\Middleware\EnsureUserIsFacility::class,
            'role.candidate' => \App\Http\Middleware\EnsureUserIsCandidate::class,
            'role.candidate_view' => \App\Http\Middleware\EnsureUserCanViewCandidates::class,
            'role.candidate_manage' => \App\Http\Middleware\EnsureUserCanManageCandidates::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ResolveOrganization::class,
            \App\Http\Middleware\SetTenantContext::class,
        ]);
        
        // Add security headers to all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Production error handling
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
