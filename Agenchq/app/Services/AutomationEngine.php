<?php

namespace App\Services;

use App\Models\AutomationLog;
use App\Models\AutomationRule;
use App\Services\Automation\Actions\CreateTaskAction;
use App\Services\Automation\Actions\GenerateInvoiceAction;
use App\Services\Automation\Actions\LogActivityAction;
use App\Services\Automation\Actions\SendEmailAction;
use App\Services\Automation\Actions\SendNotificationAction;

class AutomationEngine
{
    /**
     * @var array<string, class-string>
     */
    private array $actionMap = [
        'generate_invoice' => GenerateInvoiceAction::class,
        'send_notification' => SendNotificationAction::class,
        'log_activity' => LogActivityAction::class,
        'create_task' => CreateTaskAction::class,
        'send_email' => SendEmailAction::class,
    ];

    public function __construct(
        private InvoiceGenerationService $invoiceService,
        private NotificationService $notificationService,
        private ActivityLogger $activityLogger
    ) {}

    public function handle(object $event, int $depth = 0): void
    {
        if ($depth > 5) {
            \Illuminate\Support\Facades\Log::warning('Automation engine recursion limit reached', [
                'event' => class_basename($event),
                'tenant_id' => $event->tenantId ?? null,
            ]);
            return;
        }

        $tenantId = $event->tenantId ?? null;
        if (!$tenantId) {
            return;
        }

        $eventName = class_basename($event);

        $rules = AutomationRule::query()
            ->with(['conditions', 'actions'])
            ->where('tenant_id', $tenantId)
            ->where('event', $eventName)
            ->where('enabled', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            $passed = $this->evaluateConditions($rule->conditions->all(), $event);
            if (!$passed) {
                continue;
            }

            $this->activityLogger->log(
                tenantId: (int) $tenantId,
                entityType: 'automation_rule',
                entityId: $rule->id,
                event: 'rule_executed',
                data: ['rule_name' => $rule->name, 'trigger_event' => $eventName],
                source: 'automation'
            );

            $status = 'success';
            $errorMessages = [];

            foreach ($rule->actions as $actionRow) {
                try {
                    $this->executeAction($actionRow->action, (array) ($actionRow->config ?? []), $event);
                    
                    $this->activityLogger->log(
                        tenantId: (int) $tenantId,
                        entityType: 'automation_action',
                        entityId: $actionRow->id,
                        event: 'action_success',
                        data: ['action' => $actionRow->action, 'rule_id' => $rule->id],
                        source: 'automation'
                    );
                } catch (\Throwable $e) {
                    $status = 'partial_failure';
                    $errorMessages[] = "Action [{$actionRow->action}] failed: " . $e->getMessage();
                    
                    $this->activityLogger->log(
                        tenantId: (int) $tenantId,
                        entityType: 'automation_action',
                        entityId: $actionRow->id,
                        event: 'action_failed',
                        data: ['action' => $actionRow->action, 'rule_id' => $rule->id, 'error' => $e->getMessage()],
                        source: 'automation'
                    );

                    \Illuminate\Support\Facades\Log::error("Automation action failed", [
                        'rule_id' => $rule->id,
                        'action' => $actionRow->action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($status === 'partial_failure' && count($errorMessages) === count($rule->actions)) {
                $status = 'failed';
            }

            AutomationLog::create([
                'rule_id' => $rule->id,
                'tenant_id' => $tenantId,
                'event' => $eventName,
                'status' => $status,
                'error_message' => empty($errorMessages) ? null : implode("\n", $errorMessages),
                'executed_at' => now(),
            ]);

            if ($rule->stop_processing) {
                break;
            }
        }
    }

    private function evaluateConditions(array $conditions, object $event): bool
    {
        foreach ($conditions as $condition) {
            $actual = data_get($event, $condition->field);
            $expected = $condition->value;
            $op = strtolower((string) $condition->operator);

            if (!$this->compare($actual, $op, $expected)) {
                return false;
            }
        }

        return true;
    }

    private function compare(mixed $actual, string $op, mixed $expected): bool
    {
        if ($op === 'exists') {
            return $actual !== null;
        }

        if ($op === 'not_exists') {
            return $actual === null;
        }

        if ($op === '=' || $op === '==' || $op === 'eq') {
            return (string) $actual === (string) $expected;
        }

        if ($op === '!=' || $op === '<>' || $op === 'neq') {
            return (string) $actual !== (string) $expected;
        }

        if ($op === 'contains') {
            return str_contains((string) $actual, (string) $expected);
        }

        if (in_array($op, ['>', 'gt', '>=', 'gte', '<', 'lt', '<=', 'lte'], true)) {
            $a = is_numeric($actual) ? (float) $actual : null;
            $b = is_numeric($expected) ? (float) $expected : null;

            if ($a === null || $b === null) {
                return false;
            }

            return match ($op) {
                '>', 'gt' => $a > $b,
                '>=', 'gte' => $a >= $b,
                '<', 'lt' => $a < $b,
                '<=', 'lte' => $a <= $b,
                default => false,
            };
        }

        if ($op === 'in') {
            $list = is_array($expected) ? $expected : array_map('trim', explode(',', (string) $expected));
            return in_array((string) $actual, array_map('strval', $list), true);
        }

        return false;
    }

    private function executeAction(string $action, array $config, object $event): void
    {
        $actionKey = strtolower(trim($action));
        $class = $this->actionMap[$actionKey] ?? null;
        if (!$class) {
            throw new \RuntimeException('Unknown automation action: ' . $action);
        }

        $instance = match ($class) {
            GenerateInvoiceAction::class => new GenerateInvoiceAction($this->invoiceService),
            SendNotificationAction::class => new SendNotificationAction($this->notificationService),
            LogActivityAction::class => new LogActivityAction(),
            CreateTaskAction::class => new CreateTaskAction(),
            SendEmailAction::class => new SendEmailAction(),
            default => null,
        };

        if (!$instance) {
            throw new \RuntimeException('Automation action could not be instantiated: ' . $action);
        }

        $instance->handle($event, $config);
    }
}
