<?php

namespace App\Services;

use App\Jobs\DispatchWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;

class WebhookDispatcher
{
    /**
     * @return array<int, WebhookDelivery>
     */
    public function dispatch(int $tenantId, string $event, array $payload): array
    {
        $endpoints = WebhookEndpoint::query()
            ->where('tenant_id', $tenantId)
            ->where('enabled', true)
            ->get();

        $deliveries = [];

        foreach ($endpoints as $endpoint) {
            $events = (array) ($endpoint->events ?? []);
            if (count($events) > 0 && !in_array($event, $events, true)) {
                continue;
            }

            $delivery = WebhookDelivery::create([
                'tenant_id' => $tenantId,
                'webhook_endpoint_id' => $endpoint->id,
                'event' => $event,
                'payload' => $payload,
                'response_status' => null,
                'attempts' => 0,
                'last_attempt_at' => null,
            ]);

            DispatchWebhookJob::dispatch($delivery->id);
            $deliveries[] = $delivery;
        }

        return $deliveries;
    }
}
