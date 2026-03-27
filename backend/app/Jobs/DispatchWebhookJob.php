<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public int $deliveryId
    ) {}

    public function handle(): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);
        if (!$delivery) {
            return;
        }

        $endpoint = WebhookEndpoint::query()->find($delivery->webhook_endpoint_id);
        if (!$endpoint || !$endpoint->enabled) {
            return;
        }

        $delivery->attempts = (int) ($delivery->attempts ?? 0) + 1;
        $delivery->last_attempt_at = now();
        $delivery->save();

        $payload = (array) ($delivery->payload ?? []);
        $payload['event'] = $delivery->event;
        $timestamp = now()->toIso8601String();
        $payload['sent_at'] = $timestamp;

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $timestamp . $payloadJson, (string) $endpoint->secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-AgencyHQ-Event' => $delivery->event,
                    'X-AgencyHQ-Timestamp' => $timestamp,
                    'X-AgencyHQ-Signature' => $signature,
                ])
                ->post($endpoint->url, $payload);

            $delivery->response_status = $response->status();
            $delivery->save();

            if (!$response->successful()) {
                throw new \RuntimeException('Webhook delivery failed with status ' . $response->status());
            }
        } catch (\Throwable $e) {
            if ($delivery->response_status === null) {
                $delivery->response_status = 0;
            }
            $delivery->save();

            if ($delivery->attempts < 5) {
                $this->release($this->retryDelaySeconds($delivery->attempts));
                return;
            }

            throw $e;
        }
    }

    private function retryDelaySeconds(int $attempt): int
    {
        return match ($attempt) {
            1 => 60,
            2 => 300,
            3 => 1800,
            4 => 7200,
            default => 0,
        };
    }
}
