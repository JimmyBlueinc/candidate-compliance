<?php

namespace App\Console\Commands;

use App\Mail\CandidateCredentialExpiryReminder;
use App\Models\CandidateCredential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCredentialReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credentials:send-reminders {--days=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for credentials expiring in 30, 14, 5, or 3 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $reminderDays = [30, 14, 5, 3];
        $daysOption = $this->option('days');

        // If specific days provided, use only those
        if ($daysOption) {
            $reminderDays = array_map('intval', explode(',', $daysOption));
        }

        $totalSent = 0;

        foreach ($reminderDays as $days) {
            $this->info("Checking credentials expiring in {$days} days...");

            // Find credentials expiring exactly in $days days
            $targetDate = now()->addDays($days)->startOfDay();
            $endDate = $targetDate->copy()->endOfDay();

            $credentials = CandidateCredential::query()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '>=', $targetDate)
                ->whereDate('expires_at', '<=', $endDate)
                ->whereIn('status', ['verified', 'pending'])
                ->with(['candidate:id,user_id,name,first_name,last_name,email', 'candidate.user:id,email', 'credentialType:id,name'])
                ->get();

            $count = 0;

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

                            $candidateName = $credential->candidate?->name ?: trim(((string) ($credential->candidate?->first_name ?? '')) . ' ' . ((string) ($credential->candidate?->last_name ?? '')));
                            $typeName = $credential->credentialType?->name ?? 'Credential';
                            $this->line("  ✓ Sent reminder to {$recipientEmail} for {$candidateName} ({$typeName}, expires in {$days} days)");
                            $count++;
                            $totalSent++;
                        } catch (\Exception $e) {
                            $this->error("  ✗ Failed to send email to {$recipientEmail}: {$e->getMessage()}");
                        }
                    } else {
                        $this->warn("  ⚠ Skipped credential #{$credential->id} - no candidate email found");
                    }
                }
            }

            $this->info("  Sent {$count} reminder(s) for {$days}-day expiry.");
        }

        $this->info("\n✓ Total reminders sent: {$totalSent}");

        return Command::SUCCESS;
    }
}
