<?php

namespace App\Console\Commands;

use App\Services\Sla\SlaService;
use Illuminate\Console\Command;

class CheckSlaBreachesCommand extends Command
{
    protected $signature = 'support:check-sla-breaches';

    protected $description = 'Mark first response and resolution SLA breaches for open tickets.';

    public function handle(SlaService $sla): int
    {
        $count = $sla->markBreaches();

        $this->info("Marked {$count} ticket(s) with SLA breaches.");

        return self::SUCCESS;
    }
}
