<?php

namespace App\Listeners;

use App\Services\AutomationEngine;

class AutomationEventListener
{
    public function __construct(
        private AutomationEngine $engine
    ) {}

    public function handle(object $event): void
    {
        $this->engine->handle($event);
    }
}
