<?php

namespace App\Services;

use App\Events\JobOrderClosed;
use App\Events\JobOrderCreated;
use App\Events\JobOrderFilled;
use App\Models\JobOrder;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class JobOrderService
{
    public function createJobOrder(int $tenantId, array $data, ?User $actor = null): JobOrder
    {
        return DB::transaction(function () use ($tenantId, $data, $actor) {
            $payload = array_merge($data, [
                'tenant_id' => $tenantId,
            ]);

            if (!array_key_exists('created_by_user_id', $payload)) {
                $payload['created_by_user_id'] = $actor?->id;
            }

            if (!array_key_exists('status', $payload) || !$payload['status']) {
                $payload['status'] = 'open';
            }

            $jobOrder = JobOrder::create($payload);

            JobOrderCreated::dispatch($jobOrder, $tenantId, $actor);

            return $jobOrder;
        });
    }

    public function updateJobOrder(int $tenantId, int $jobOrderId, array $data, ?User $actor = null): JobOrder
    {
        return DB::transaction(function () use ($tenantId, $jobOrderId, $data, $actor) {
            $jobOrder = JobOrder::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($jobOrderId);

            $previousStatus = (string) $jobOrder->status;

            $jobOrder->fill(Arr::except($data, ['tenant_id', 'id']));
            $jobOrder->save();

            $status = (string) $jobOrder->status;
            if ($previousStatus !== $status && $status === 'filled') {
                JobOrderFilled::dispatch($jobOrder, $tenantId, $previousStatus, $status, $actor);
            }

            return $jobOrder;
        });
    }

    public function closeJobOrder(int $tenantId, int $jobOrderId, ?User $actor = null): JobOrder
    {
        return DB::transaction(function () use ($tenantId, $jobOrderId, $actor) {
            $jobOrder = JobOrder::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($jobOrderId);

            $previousStatus = (string) $jobOrder->status;
            if ($previousStatus === 'cancelled') {
                return $jobOrder;
            }

            $jobOrder->status = 'cancelled';
            $jobOrder->save();

            JobOrderClosed::dispatch($jobOrder, $tenantId, $previousStatus, 'cancelled', $actor);

            return $jobOrder;
        });
    }

    public function reopenJobOrder(int $tenantId, int $jobOrderId, ?User $actor = null): JobOrder
    {
        return DB::transaction(function () use ($tenantId, $jobOrderId) {
            $jobOrder = JobOrder::query()
                ->where('tenant_id', $tenantId)
                ->findOrFail($jobOrderId);

            if ((string) $jobOrder->status === 'open') {
                return $jobOrder;
            }

            $jobOrder->status = 'open';
            $jobOrder->save();

            return $jobOrder;
        });
    }
}
