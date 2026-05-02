<?php

namespace App\Jobs;

use App\Models\TicketEvent;
use App\Services\Automation\AutomationRuleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunAutomationRules implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TicketEvent $event) {}

    public function handle(AutomationRuleService $automation): void
    {
        $automation->runForEvent($this->event->refresh());
    }
}
