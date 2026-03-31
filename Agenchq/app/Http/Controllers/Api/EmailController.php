<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CandidateCredentialExpiryReminder;
use App\Mail\CredentialExpirySummary;
use App\Models\CandidateCredential;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use App\Support\Org;

class EmailController extends Controller
{
    /**
     * Send reminder emails.
     * 
     * Manual trigger (from button): Sends to all candidate credentials with expiry dates.
     * Automatic (scheduled): Sends only to credentials expiring in 30/14/5/3 days.
     */
    public function sendReminders(Request $request): JsonResponse
    {
        $sendToAll = $request->boolean('send_to_all', true); // Default to true for manual button clicks
        $daysOption = $request->input('days');
        $orgId = Org::id($request);

        $totalSent = 0;
        $errors = [];

        if ($sendToAll) {
            // Manual trigger: Send to ALL candidates with expiry dates
            $credentials = CandidateCredential::query()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '>=', now()->startOfDay())
                ->when($orgId, fn ($q) => $q->where('tenant_id', $orgId))
                ->with(['candidate:id,user_id,name,first_name,last_name,email', 'candidate.user:id,email', 'credentialType:id,name'])
                ->get();

            foreach ($credentials as $credential) {
                $recipientEmail = $credential->candidate?->email ?: $credential->candidate?->user?->email;
                if ($recipientEmail) {
                    $daysUntilExpiry = now()->startOfDay()->diffInDays($credential->expires_at->startOfDay(), false);
                    $reminderDays = $daysUntilExpiry > 0 ? $daysUntilExpiry : 0;
                    try {
                        Mail::to($recipientEmail)->send(
                            new CandidateCredentialExpiryReminder($credential, $reminderDays)
                        );
                        $totalSent++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to send email to {$recipientEmail}: {$e->getMessage()}";
                    }
                }
            }
        } else {
            // Automatic: Send only to candidates expiring in 30, 14, 5, or 3 days
            $reminderDays = [30, 14, 5, 3];
            
            // If specific days provided, use only those
            if ($daysOption) {
                $reminderDays = array_map('intval', explode(',', $daysOption));
            }

            foreach ($reminderDays as $days) {
                // Find credentials expiring exactly in $days days
                $targetDate = now()->addDays($days)->startOfDay();
                $endDate = $targetDate->copy()->endOfDay();

                $credentials = CandidateCredential::query()
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '>=', $targetDate)
                    ->whereDate('expires_at', '<=', $endDate)
                    ->whereIn('status', ['verified', 'pending'])
                    ->when($orgId, fn ($q) => $q->where('tenant_id', $orgId))
                    ->with(['candidate:id,user_id,name,first_name,last_name,email', 'candidate.user:id,email', 'credentialType:id,name'])
                    ->get();

                foreach ($credentials as $credential) {
                    // Verify it's exactly $days away
                    $daysUntilExpiry = now()->startOfDay()->diffInDays($credential->expires_at->startOfDay(), false);

                    if ($daysUntilExpiry == $days) {
                        $recipientEmail = $credential->candidate?->email ?: $credential->candidate?->user?->email;
                        if ($recipientEmail) {
                            try {
                                Mail::to($recipientEmail)->send(
                                    new CandidateCredentialExpiryReminder($credential, $days)
                                );
                                $totalSent++;
                            } catch (\Exception $e) {
                                $errors[] = "Failed to send email to {$recipientEmail}: {$e->getMessage()}";
                            }
                        }
                    }
                }
            }
        }

        return response()->json([
            'message' => $sendToAll 
                ? "Reminder emails sent to all candidates successfully" 
                : "Reminder emails sent successfully",
            'total_sent' => $totalSent,
            'errors' => $errors,
        ]);
    }

    /**
     * Send daily summary email to Admin users.
     */
    public function sendSummary(Request $request): JsonResponse
    {
        $orgId = Org::id($request);
        // Find all credentials expiring within the next 30 days
        $today = now()->startOfDay();
        $thirtyDaysFromNow = now()->addDays(30)->endOfDay();

        $credentials = Credential::whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $thirtyDaysFromNow)
            ->whereNotNull('expiry_date')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->with('user')
            ->orderBy('expiry_date', 'asc')
            ->get();

        // Find admin and org_super_admin users
        $admins = User::whereIn('role', ['admin', 'org_super_admin'])
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();

        if ($admins->isEmpty()) {
            return response()->json([
                'message' => 'No admin users found',
                'total_sent' => 0,
            ], 400);
        }

        $sentCount = 0;
        $errors = [];

        foreach ($admins as $admin) {
            if (!$admin->email) {
                continue;
            }

            try {
                Mail::to($admin->email)->send(
                    new CredentialExpirySummary($credentials)
                );
                $sentCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to send email to {$admin->email}: {$e->getMessage()}";
            }
        }

        return response()->json([
            'message' => "Summary emails sent successfully",
            'total_sent' => $sentCount,
            'credentials_count' => $credentials->count(),
            'errors' => $errors,
        ]);
    }
}

