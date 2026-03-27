<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Assignment;
use App\Models\Candidate;
use App\Models\CandidateAvailability;
use App\Models\Organization;
use App\Models\Shift;
use App\Models\ShiftRequest;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\AvailabilityIndexService;
use App\Services\AvailabilityService;
use App\Services\ShiftAssignmentService;
use App\Services\ShiftService;
use App\Support\TenantContext;
use Carbon\Carbon;

function out(string $msg): void
{
    echo $msg . PHP_EOL;
}

out('== Phase 5 Runtime Validation ==');

$org = Organization::query()->where('slug', 'validation-org')->first();
if (!$org) {
    out('ERROR: validation-org not found. Run Phase 3 validation script first.');
    exit(1);
}
TenantContext::setId($org->id);

/** @var ShiftService $shiftService */
$shiftService = app(ShiftService::class);
/** @var ShiftAssignmentService $assignmentService */
$assignmentService = app(ShiftAssignmentService::class);
/** @var AvailabilityIndexService $index */
$index = app(AvailabilityIndexService::class);
/** @var AvailabilityService $availability */
$availability = app(AvailabilityService::class);

$assignment = Assignment::query()->where('tenant_id', $org->id)->where('status', 'active')->orderByDesc('id')->first();
if (!$assignment) {
    out('ERROR: active assignment not found.');
    exit(1);
}

$candidate = Candidate::query()->where('tenant_id', $org->id)->where('id', $assignment->candidate_id)->first();
if (!$candidate) {
    out('ERROR: candidate not found for assignment.');
    exit(1);
}

$template = ShiftTemplate::query()->where('tenant_id', $org->id)->orderByDesc('id')->first();
if (!$template) {
    out('ERROR: shift template not found.');
    exit(1);
}

// Set declared availability Monday 07:00-15:00
CandidateAvailability::query()->withoutGlobalScopes()
    ->where('tenant_id', $org->id)
    ->where('candidate_id', $candidate->id)
    ->delete();

CandidateAvailability::create([
    'tenant_id' => $org->id,
    'candidate_id' => $candidate->id,
    'day_of_week' => 1,
    'start_time' => '07:00:00',
    'end_time' => '15:00:00',
    'is_available' => true,
]);

out('DECLARED_AVAILABILITY_OK Monday 07:00-15:00');

// Choose next Monday date
$date = now()->next(Carbon::MONDAY)->toDateString();

// Create shift Monday 09:00-17:00 by temporarily using template times
$origStart = (string) $template->start_time;
$origEnd = (string) $template->end_time;

template_update($template, '09:00:00', '17:00:00');
$shift = $shiftService->createShiftFromTemplate($org->id, $template->id, $date, $assignment->id, null, null);
$template->start_time = $origStart;
$template->end_time = $origEnd;
$template->save();

out('SHIFT_CREATED shift_id=' . $shift->id);

$startsAt = Carbon::parse($shift->starts_at)->utc();
$endsAt = Carbon::parse($shift->ends_at)->utc();
$outcome = $availability->evaluateWindow($org->id, $candidate->id, $startsAt, $endsAt);
out('EVAL_BEFORE_BLACKOUT status=' . ($outcome['status'] ?? '') . ' hard_block=' . (($outcome['hard_block'] ?? false) ? 'true' : 'false'));

// Add blackout Monday 09:00-12:00
$blackoutStart = Carbon::parse($date . ' 09:00:00', 'UTC');
$blackoutEnd = Carbon::parse($date . ' 12:00:00', 'UTC');
$index->markCandidateUnavailable($candidate->id, $blackoutStart->toIso8601String(), $blackoutEnd->toIso8601String());
out('BLACKOUT_OK 09:00-12:00');

// Create pending request
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

out('REQUEST_READY id=' . $request->id);

try {
    $assignmentService->approveRequest($org->id, $request->id, User::query()->where('organization_id', $org->id)->where('role', 'scheduler')->first());
    out('APPROVAL_UNEXPECTED_SUCCESS');
} catch (Throwable $e) {
    out('APPROVAL_BLOCKED_OK=' . $e->getMessage());
}

function template_update(ShiftTemplate $template, string $start, string $end): void
{
    $template->start_time = $start;
    $template->end_time = $end;
    $template->save();
}
