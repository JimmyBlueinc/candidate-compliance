<?php

namespace App\Services;

use App\Models\CandidateAvailability;
use App\Models\CandidateShiftIndex;
use Carbon\Carbon;

class AvailabilityService
{
    public function getRecurringAvailability(int $tenantId, int $candidateId): array
    {
        return CandidateAvailability::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn (CandidateAvailability $w) => [
                'id' => $w->id,
                'day_of_week' => (int) $w->day_of_week,
                'start_time' => (string) $w->start_time,
                'end_time' => (string) $w->end_time,
                'is_available' => (bool) $w->is_available,
            ])
            ->all();
    }

    /**
     * Evaluate effective availability for a given shift window.
     *
     * Returns:
     * - status: available | blackout_conflict | declared_unavailable | outside_declared | no_declared
     * - hard_block: bool
     */
    public function evaluateWindow(int $tenantId, int $candidateId, $startsAt, $endsAt): array
    {
        $startUtc = Carbon::parse($startsAt)->utc();
        $endUtc = Carbon::parse($endsAt)->utc();

        $blackout = $this->hasBlackoutConflict($tenantId, $candidateId, $startUtc, $endUtc);
        if ($blackout) {
            return [
                'status' => 'blackout_conflict',
                'hard_block' => true,
            ];
        }

        $declared = CandidateAvailability::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)
            ->get(['day_of_week', 'start_time', 'end_time', 'is_available']);

        if ($declared->count() === 0) {
            return [
                'status' => 'no_declared',
                'hard_block' => false,
            ];
        }

        $segments = $this->splitIntoUtcDaySegments($startUtc, $endUtc);

        $allCovered = true;
        foreach ($segments as $seg) {
            $dow = (int) $seg['dow']; // ISO 1-7
            $segStartSec = (int) $seg['start_sec'];
            $segEndSec = (int) $seg['end_sec'];

            $matchingDay = $declared->filter(fn ($w) => (int) $w->day_of_week === $dow);
            if ($matchingDay->count() === 0) {
                $allCovered = false;
                continue;
            }

            foreach ($matchingDay as $w) {
                if ((bool) $w->is_available !== false) {
                    continue;
                }

                $wStart = $this->timeToSeconds((string) $w->start_time);
                $wEnd = $this->timeToSeconds((string) $w->end_time);
                if ($this->secsOverlap($segStartSec, $segEndSec, $wStart, $wEnd)) {
                    return [
                        'status' => 'declared_unavailable',
                        'hard_block' => true,
                    ];
                }
            }

            $covered = false;
            foreach ($matchingDay as $w) {
                if ((bool) $w->is_available !== true) {
                    continue;
                }

                $wStart = $this->timeToSeconds((string) $w->start_time);
                $wEnd = $this->timeToSeconds((string) $w->end_time);
                if ($this->secsCovers($segStartSec, $segEndSec, $wStart, $wEnd)) {
                    $covered = true;
                    break;
                }
            }

            if (!$covered) {
                $allCovered = false;
            }
        }

        if ($allCovered) {
            return [
                'status' => 'available',
                'hard_block' => false,
            ];
        }

        return [
            'status' => 'outside_declared',
            'hard_block' => false,
        ];
    }

    public function assertNotHardBlocked(int $tenantId, int $candidateId, $startsAt, $endsAt): void
    {
        $result = $this->evaluateWindow($tenantId, $candidateId, $startsAt, $endsAt);
        if (($result['hard_block'] ?? false) === true) {
            $status = (string) ($result['status'] ?? '');
            if ($status === 'blackout_conflict') {
                throw new \RuntimeException('Candidate is unavailable for this shift.');
            }
            if ($status === 'declared_unavailable') {
                throw new \RuntimeException('Candidate has declared they are unavailable for this shift.');
            }
            throw new \RuntimeException('Candidate is unavailable for this shift.');
        }
    }

    private function hasBlackoutConflict(int $tenantId, int $candidateId, Carbon $startUtc, Carbon $endUtc): bool
    {
        $rows = CandidateShiftIndex::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_id', $candidateId)
            ->where('is_available', false)
            ->whereDate('date', '>=', $startUtc->toDateString())
            ->whereDate('date', '<=', $endUtc->toDateString())
            ->get(['date', 'start_time', 'end_time']);

        foreach ($rows as $row) {
            $blockStart = Carbon::parse($row->date->format('Y-m-d') . ' ' . $row->start_time, 'UTC');
            $blockEnd = Carbon::parse($row->date->format('Y-m-d') . ' ' . $row->end_time, 'UTC');
            if ($blockEnd->lte($blockStart)) {
                $blockEnd = $blockEnd->addDay();
            }

            if ($blockStart->lt($endUtc) && $blockEnd->gt($startUtc)) {
                return true;
            }
        }

        return false;
    }

    private function splitIntoUtcDaySegments(Carbon $startUtc, Carbon $endUtc): array
    {
        if ($endUtc->lte($startUtc)) {
            return [];
        }

        $segments = [];
        $cursor = $startUtc->copy();

        while ($cursor->lt($endUtc)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay()->addSecond();
            $segEnd = $endUtc->lt($dayEnd) ? $endUtc->copy() : $dayEnd;

            $startSec = (int) $dayStart->diffInSeconds($cursor);
            $endSec = (int) $dayStart->diffInSeconds($segEnd);

            $startSec = max(0, min(86400, $startSec));
            $endSec = max(0, min(86400, $endSec));

            $segments[] = [
                'date' => $dayStart->toDateString(),
                'dow' => (int) $dayStart->dayOfWeekIso,
                'start_sec' => $startSec,
                'end_sec' => $endSec,
            ];

            $cursor = $segEnd;
        }

        return $segments;
    }

    private function timeToSeconds(string $time): int
    {
        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);
        return ($h * 3600) + ($m * 60) + $s;
    }

    private function secsCovers(int $segStart, int $segEnd, int $winStart, int $winEnd): bool
    {
        return $segStart >= $winStart && $segEnd <= $winEnd;
    }

    private function secsOverlap(int $aStart, int $aEnd, int $bStart, int $bEnd): bool
    {
        return $aStart < $bEnd && $aEnd > $bStart;
    }
}
