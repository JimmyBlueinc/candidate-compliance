<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\CandidateShiftIndex;
use App\Models\Facility;
use App\Models\JobOrder;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\Shift;
use App\Models\ShiftRequest;
use App\Models\ShiftTemplate;
use App\Services\AvailabilityIndexService;
use App\Services\OperationalPlacementService;
use App\Services\ShiftAssignmentService;
use App\Services\ShiftService;
use App\Support\TenantContext;
use App\Models\User;
use Carbon\Carbon;

function out(string $msg): void
{
    echo $msg . PHP_EOL;
}

out('== Phase 4 Runtime Validation ==');

$org = Organization::query()->where('slug', 'validation-org')->first();
if (!$org) {
    out('ERROR: validation-org not found. Run Phase 3 validation script first.');
    exit(1);
}
TenantContext::setId($org->id);

$candidate = Candidate::query()->where('tenant_id', $org->id)->first();
$assignment = Assignment::query()->where('tenant_id', $org->id)->where('status', 'active')->first();

// Create a fresh candidate + active assignment to ensure there are no prior overlaps.
$facility = Facility::query()->where('organization_id', $org->id)->orderByDesc('id')->first();
if (!$facility) {
    out('ERROR: facility not found in tenant.');
    exit(1);
}

$suffix = (string) now()->timestamp;
$candidateEmail = "phase4.candidate.{$suffix}@example.com";

$candidateUser = User::firstOrCreate(
    ['organization_id' => $org->id, 'email' => $candidateEmail],
    [
        'name' => "Phase4 Candidate {$suffix}",
        'password' => bcrypt('password'),
        'role' => 'candidate',
        'access_status' => 'active',
        'must_change_password' => 0,
    ]
);

$candidate = Candidate::firstOrCreate(
    ['tenant_id' => $org->id, 'email' => $candidateEmail],
    [
        'name' => "Phase4 Candidate {$suffix}",
        'first_name' => 'Phase4',
        'last_name' => "Candidate {$suffix}",
        'user_id' => $candidateUser->id,
    ]
);

$jobOrder = JobOrder::firstOrCreate(
    [
        'tenant_id' => $org->id,
        'title' => "Phase4 Validation Job {$suffix}",
        'facility_id' => $facility->id,
        'facility_name' => $facility->name,
    ],
    ['status' => 'open']
);

$placement = Placement::firstOrCreate(
    ['tenant_id' => $org->id, 'candidate_id' => $candidate->id, 'job_order_id' => $jobOrder->id],
    ['recruiter_id' => $candidateUser->id, 'stage' => 'placed']
);

$assignment = app(OperationalPlacementService::class)->createFromPipelinePlacement($org->id, (int) $placement->id);
$assignment->facility_id = $facility->id;
$assignment->status = 'active';
$assignment->start_date = now()->startOfWeek();
$assignment->save();

/** @var ShiftService $shiftService */
$shiftService = app(ShiftService::class);
/** @var ShiftAssignmentService $assignmentService */
$assignmentService = app(ShiftAssignmentService::class);
/** @var AvailabilityIndexService $availability */
$availability = app(AvailabilityIndexService::class);

// Create a future open shift to avoid overlap with already-approved shifts
$template = ShiftTemplate::query()->where('tenant_id', $org->id)->orderByDesc('id')->first();
if (!$template) {
    out('ERROR: no shift template found in tenant.');
    exit(1);
}

$date = now()->addDays(7)->toDateString();
$shift = $shiftService->createShiftFromTemplate($org->id, $template->id, $date, $assignment->id, null, null);

$startsAt = Carbon::parse($shift->starts_at)->utc();
$endsAt = Carbon::parse($shift->ends_at)->utc();
out('SHIFT_ID=' . $shift->id);
out('SHIFT_STARTS_AT=' . $startsAt->toIso8601String());
out('SHIFT_ENDS_AT=' . $endsAt->toIso8601String());

// Mark candidate unavailable overlapping this shift
$unavailStart = $startsAt->copy()->addMinutes(30);
$unavailEnd = $startsAt->copy()->addHours(1);
$availability->markCandidateUnavailable($candidate->id, $unavailStart->toIso8601String(), $unavailEnd->toIso8601String());
out('MARKED_UNAVAILABLE_OK');

$totalRows = CandidateShiftIndex::query()->withoutGlobalScopes()
    ->where('tenant_id', $org->id)
    ->where('candidate_id', $candidate->id)
    ->count();
out('INDEX_TOTAL_ROWS=' . $totalRows);

$latest = CandidateShiftIndex::query()->withoutGlobalScopes()
    ->where('tenant_id', $org->id)
    ->where('candidate_id', $candidate->id)
    ->orderByDesc('id')
    ->limit(5)
    ->get(['id', 'date', 'start_time', 'end_time', 'is_available']);

foreach ($latest as $row) {
    out('INDEX_ROW id=' . $row->id . ' date=' . $row->date?->format('Y-m-d') . ' start=' . $row->start_time . ' end=' . $row->end_time . ' avail=' . ($row->is_available ? 'true' : 'false'));
}

out('CHECK_CONFLICT_LOCAL=begin');
$rows = CandidateShiftIndex::query()->withoutGlobalScopes()
    ->where('tenant_id', $org->id)
    ->where('candidate_id', $candidate->id)
    ->where('is_available', false)
    ->whereBetween('date', [$startsAt->toDateString(), $endsAt->toDateString()])
    ->get(['date', 'start_time', 'end_time']);

$localConflict = false;
foreach ($rows as $r) {
    $blockStart = Carbon::parse($r->date->format('Y-m-d') . ' ' . $r->start_time, 'UTC');
    $blockEnd = Carbon::parse($r->date->format('Y-m-d') . ' ' . $r->end_time, 'UTC');
    if ($blockEnd->lte($blockStart)) {
        $blockEnd = $blockEnd->addDay();
    }

    $overlaps = $blockStart->lt($endsAt) && $blockEnd->gt($startsAt);
    out('CHECK_ROW overlaps=' . ($overlaps ? 'true' : 'false') . ' blockStart=' . $blockStart->toIso8601String() . ' blockEnd=' . $blockEnd->toIso8601String());
    if ($overlaps) {
        $localConflict = true;
    }
}
out('CHECK_CONFLICT_LOCAL=' . ($localConflict ? 'true' : 'false'));

$exists = CandidateShiftIndex::query()->withoutGlobalScopes()
    ->where('tenant_id', $org->id)
    ->where('candidate_id', $candidate->id)
    ->where('is_available', false)
    ->whereDate('date', $unavailStart->toDateString())
    ->exists();
out('INDEX_ROW_EXISTS=' . ($exists ? 'true' : 'false'));

// Ensure a pending request exists for the shift
$request = ShiftRequest::query()
    ->where('tenant_id', $org->id)
    ->where('shift_id', $shift->id)
    ->where('candidate_id', $candidate->id)
    ->orderByDesc('id')
    ->first();

if (!$request) {
    $request = $shiftService->requestShift($org->id, $shift->id, $candidate->id, null, null);
}

if ((string) $request->status !== 'pending') {
    $request->status = 'pending';
    $request->responded_at = null;
    $request->responded_by_user_id = null;
    $request->rejection_reason = null;
    $request->save();
}

out('REQUEST_ID=' . $request->id);

try {
    $assignmentService->approveRequest($org->id, $request->id, null);
    out('AVAILABILITY_BLOCK_FAILED');
} catch (Throwable $e) {
    out('AVAILABILITY_BLOCKED_OK=' . $e->getMessage());
}
