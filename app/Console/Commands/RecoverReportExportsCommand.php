<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RecoverReportExportsCommand extends Command
{
    protected $signature = 'support:recover-report-exports {--minutes=10 : Age in minutes before pending/processing exports are considered stale.}';

    protected $description = 'Requeue pending and stale processing report exports.';

    public function handle(): int
    {
        $threshold = now()->subMinutes(max(1, (int) $this->option('minutes')));

        $exports = ReportExport::query()
            ->where(function (Builder $query) use ($threshold): void {
                $query
                    ->where(function (Builder $query) use ($threshold): void {
                        $query
                            ->where('status', ReportExport::STATUS_PENDING)
                            ->where('created_at', '<=', $threshold);
                    })
                    ->orWhere(function (Builder $query) use ($threshold): void {
                        $query
                            ->where('status', ReportExport::STATUS_PROCESSING)
                            ->where('updated_at', '<=', $threshold);
                    });
            })
            ->latest()
            ->limit(50)
            ->get();

        foreach ($exports as $export) {
            $export->update([
                'status' => ReportExport::STATUS_PENDING,
                'error_message' => null,
            ]);

            GenerateReportExport::dispatch($export)->onQueue('reports');
        }

        $this->info("Requeued {$exports->count()} report export(s).");

        return self::SUCCESS;
    }
}
