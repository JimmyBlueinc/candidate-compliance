<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Candidate;
use App\Models\Facility;
use App\Models\JobOrder;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\OperationalPlacementService;
use App\Services\ShiftAssignmentService;
use App\Services\ShiftService;
use App\Support\TenantContext;

function out(string $msg): void {
    echo $msg . PHP_EOL;
}

$org = Organization::firstOrCreate(
    ['slug' => 'validation-org'],
    ['name' => 'Validation Org']
);
TenantContext::setId($org->id);

$facility = Facility::firstOrCreate([
    'organization_id' => $org->id,
    'name' => 'Validation Facility',
]);

$scheduler = User::firstOrCreate(
    ['organization_id' => $org->id, 'email' => 'scheduler.validation@example.com'],
    [
        'name' => 'Scheduler Validation',
        'password' => bcrypt('password'),
        'role' => 'scheduler',
        'access_status' => 'active',
        'must_change_password' => 0,
    ]
);

$candidateUser = User::firstOrCreate(
    ['organization_id' => $org->id, 'email' => 'candidate.validation@example.com'],
    [
        'name' => 'Candidate Validation',
        'password' => bcrypt('password'),
        'role' => 'candidate',
        'access_status' => 'active',
        'must_change_password' => 0,
    ]
);

$candidate = Candidate::firstOrCreate(
    ['tenant_id' => $org->id, 'email' => $candidateUser->email],
    [
        'name' => 'Candidate Validation',
        'first_name' => 'Candidate',
        'last_name' => 'Validation',
        'user_id' => $candidateUser->id,
    ]
);

$jobOrder = JobOrder::firstOrCreate(
    [
        'tenant_id' => $org->id,
        'title' => 'Validation Job',
        'facility_id' => $facility->id,
        'facility_name' => $facility->name,
    ],
    ['status' => 'open']
);

$placement = Placement::firstOrCreate(
    ['tenant_id' => $org->id, 'candidate_id' => $candidate->id, 'job_order_id' => $jobOrder->id],
    ['recruiter_id' => $scheduler->id, 'stage' => 'placed']
);

$assignment = app(OperationalPlacementService::class)->createFromPipelinePlacement($org->id, (int) $placement->id);
$assignment->facility_id = $facility->id;
$assignment->status = 'active';
$assignment->start_date = now()->startOfWeek();
$assignment->save();

$template = ShiftTemplate::firstOrCreate(
    ['tenant_id' => $org->id, 'facility_id' => $facility->id, 'name' => 'Day Shift'],
    ['start_time' => '08:00', 'end_time' => '16:00', 'timezone' => 'UTC', 'active' => true]
);

/** @var ShiftService $shiftService */
$shiftService = app(ShiftService::class);
/** @var ShiftAssignmentService $assignmentService */
$assignmentService = app(ShiftAssignmentService::class);

out('== Phase 3 Runtime Validation ==');

// 1) Scheduler creates shift
$shift1 = $shiftService->createShiftFromTemplate(
    $org->id,
    $template->id,
    now()->toDateString(),
    $assignment->id,
    null,
    $scheduler
);
out('SHIFT1_CREATED_ID=' . $shift1->id);

// 2) Candidate requests shift
$req1 = $shiftService->requestShift($org->id, $shift1->id, $candidate->id, null, $candidateUser);
out('REQUEST1_ID=' . $req1->id);

// Approve request (creates ShiftAssignment)
$approved1 = $assignmentService->approveRequest($org->id, $req1->id, $scheduler);
out('REQUEST1_APPROVED_ASSIGNMENT_ID=' . $approved1->id);

// 3) Overlap block test: create overlapping shift, request, attempt approval
$shift2 = $shiftService->createShiftFromTemplate(
    $org->id,
    $template->id,
    now()->toDateString(),
    $assignment->id,
    null,
    $scheduler
);
$shift2->starts_at = $shift1->starts_at->copy()->addHours(2);
$shift2->ends_at = $shift1->ends_at->copy()->addHours(2);
$shift2->save();
out('SHIFT2_CREATED_ID=' . $shift2->id);

$req2 = $shiftService->requestShift($org->id, $shift2->id, $candidate->id, null, $candidateUser);
out('REQUEST2_ID=' . $req2->id);

try {
    $assignmentService->approveRequest($org->id, $req2->id, $scheduler);
    out('OVERLAP_CHECK_FAILED');
} catch (Throwable $e) {
    out('OVERLAP_BLOCKED_OK=' . $e->getMessage());
}

// 4) Completion creates timesheet draft
$result = $shiftService->completeShift($org->id, $shift1->id, $scheduler);
$timesheet = $result['timesheet'] ?? null;
out('SHIFT1_COMPLETED');
out('TIMESHEET_DRAFT_CREATED_ID=' . ($timesheet?->id ?: 'null'));
out('TIMESHEET_STATUS=' . ($timesheet?->status ?: 'null'));
