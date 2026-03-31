<?php

namespace App\Providers;

use App\Events\CredentialExpired;
use App\Events\CredentialExpiringSoon;
use App\Events\CredentialRejected;
use App\Events\CredentialUploaded;
use App\Events\CredentialVerified;
use App\Events\CandidateRecruiterAssigned;
use App\Events\CandidateStageChanged;
use App\Events\JobOrderClosed;
use App\Events\JobOrderCreated;
use App\Events\JobOrderFilled;
use App\Events\InvoiceGenerated;
use App\Events\MessageCreated;
use App\Events\PlacementCreated;
use App\Events\ShiftAssignmentApproved;
use App\Events\ShiftAssignmentRejected;
use App\Events\ShiftCancelled;
use App\Events\ShiftCompleted;
use App\Events\ShiftCreated;
use App\Events\ShiftRequestWithdrawn;
use App\Events\ShiftRequested;
use App\Events\SubmissionAccepted;
use App\Events\TimesheetApproved;
use App\Events\TimesheetSubmitted;
use App\Listeners\AvailabilityIndexListener;
use App\Listeners\ActivityLogListener;
use App\Listeners\AutomationEventListener;
use App\Listeners\CandidatePipelineCommunicationListener;
use App\Listeners\CredentialCommunicationListener;
use App\Listeners\GenerateInvoiceListener;
use App\Listeners\InvoiceCommunicationListener;
use App\Listeners\JobOrderCommunicationListener;
use App\Listeners\NotifyFacilityListener;
use App\Listeners\NotifyRecruiterListener;
use App\Listeners\ShiftCommunicationListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Response::macro('api', function (mixed $data = null, int $status = 200, array $meta = [], ?string $message = null) {
            return response()->json([
                'data' => $data,
                'meta' => (object) $meta,
                'message' => $message,
            ], $status);
        });

        Event::listen(TimesheetSubmitted::class, NotifyFacilityListener::class);
        Event::listen(TimesheetApproved::class, GenerateInvoiceListener::class);
        Event::listen(InvoiceGenerated::class, NotifyFacilityListener::class);

        Event::listen(SubmissionAccepted::class, NotifyRecruiterListener::class);
        Event::listen(MessageCreated::class, NotifyRecruiterListener::class);

        Event::listen(PlacementCreated::class, ActivityLogListener::class);

        Event::listen(TimesheetApproved::class, AutomationEventListener::class);
        Event::listen(SubmissionAccepted::class, AutomationEventListener::class);
        Event::listen(PlacementCreated::class, AutomationEventListener::class);
        Event::listen(InvoiceGenerated::class, AutomationEventListener::class);
        Event::listen(MessageCreated::class, AutomationEventListener::class);

        Event::listen(TimesheetSubmitted::class, ActivityLogListener::class);
        Event::listen(TimesheetApproved::class, ActivityLogListener::class);
        Event::listen(InvoiceGenerated::class, ActivityLogListener::class);
        Event::listen(SubmissionAccepted::class, ActivityLogListener::class);
        Event::listen(MessageCreated::class, ActivityLogListener::class);

        Event::listen(InvoiceGenerated::class, InvoiceCommunicationListener::class);

        Event::listen(CredentialUploaded::class, AutomationEventListener::class);
        Event::listen(CredentialVerified::class, AutomationEventListener::class);
        Event::listen(CredentialRejected::class, AutomationEventListener::class);
        Event::listen(CredentialExpiringSoon::class, AutomationEventListener::class);
        Event::listen(CredentialExpired::class, AutomationEventListener::class);

        Event::listen(CredentialUploaded::class, ActivityLogListener::class);
        Event::listen(CredentialVerified::class, ActivityLogListener::class);
        Event::listen(CredentialRejected::class, ActivityLogListener::class);
        Event::listen(CredentialExpiringSoon::class, ActivityLogListener::class);
        Event::listen(CredentialExpired::class, ActivityLogListener::class);

        Event::listen(CredentialUploaded::class, CredentialCommunicationListener::class);
        Event::listen(CredentialVerified::class, CredentialCommunicationListener::class);
        Event::listen(CredentialRejected::class, CredentialCommunicationListener::class);
        Event::listen(CredentialExpiringSoon::class, CredentialCommunicationListener::class);
        Event::listen(CredentialExpired::class, CredentialCommunicationListener::class);

        Event::listen(CredentialVerified::class, AvailabilityIndexListener::class);
        Event::listen(CredentialExpired::class, AvailabilityIndexListener::class);

        Event::listen(CandidateStageChanged::class, AutomationEventListener::class);
        Event::listen(CandidateRecruiterAssigned::class, AutomationEventListener::class);

        Event::listen(CandidateStageChanged::class, ActivityLogListener::class);
        Event::listen(CandidateRecruiterAssigned::class, ActivityLogListener::class);

        Event::listen(CandidateStageChanged::class, CandidatePipelineCommunicationListener::class);
        Event::listen(CandidateRecruiterAssigned::class, CandidatePipelineCommunicationListener::class);

        Event::listen(JobOrderCreated::class, AutomationEventListener::class);
        Event::listen(JobOrderFilled::class, AutomationEventListener::class);
        Event::listen(JobOrderClosed::class, AutomationEventListener::class);

        Event::listen(JobOrderCreated::class, ActivityLogListener::class);
        Event::listen(JobOrderFilled::class, ActivityLogListener::class);
        Event::listen(JobOrderClosed::class, ActivityLogListener::class);

        Event::listen(JobOrderCreated::class, JobOrderCommunicationListener::class);
        Event::listen(JobOrderFilled::class, JobOrderCommunicationListener::class);
        Event::listen(JobOrderClosed::class, JobOrderCommunicationListener::class);

        Event::listen(ShiftCreated::class, AutomationEventListener::class);
        Event::listen(ShiftRequested::class, AutomationEventListener::class);
        Event::listen(ShiftRequestWithdrawn::class, AutomationEventListener::class);
        Event::listen(ShiftAssignmentApproved::class, AutomationEventListener::class);
        Event::listen(ShiftAssignmentRejected::class, AutomationEventListener::class);
        Event::listen(ShiftCompleted::class, AutomationEventListener::class);
        Event::listen(ShiftCancelled::class, AutomationEventListener::class);

        Event::listen(ShiftCreated::class, ActivityLogListener::class);
        Event::listen(ShiftRequested::class, ActivityLogListener::class);
        Event::listen(ShiftRequestWithdrawn::class, ActivityLogListener::class);
        Event::listen(ShiftAssignmentApproved::class, ActivityLogListener::class);
        Event::listen(ShiftAssignmentRejected::class, ActivityLogListener::class);
        Event::listen(ShiftCompleted::class, ActivityLogListener::class);
        Event::listen(ShiftCancelled::class, ActivityLogListener::class);

        Event::listen(ShiftCreated::class, ShiftCommunicationListener::class);
        Event::listen(ShiftRequested::class, ShiftCommunicationListener::class);
        Event::listen(ShiftRequestWithdrawn::class, ShiftCommunicationListener::class);
        Event::listen(ShiftAssignmentApproved::class, ShiftCommunicationListener::class);
        Event::listen(ShiftAssignmentRejected::class, ShiftCommunicationListener::class);
        Event::listen(ShiftCompleted::class, ShiftCommunicationListener::class);
        Event::listen(ShiftCancelled::class, ShiftCommunicationListener::class);

        Event::listen(ShiftAssignmentApproved::class, AvailabilityIndexListener::class);
        Event::listen(ShiftCompleted::class, AvailabilityIndexListener::class);
        Event::listen(ShiftCancelled::class, AvailabilityIndexListener::class);
    }
}
