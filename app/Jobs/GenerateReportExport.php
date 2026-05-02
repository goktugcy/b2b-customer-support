<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\Reports\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly ReportExport $export) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ReportExportService $reports): void
    {
        $reports->generate($this->export->refresh());
    }

    public function failed(?Throwable $exception): void
    {
        $this->export->refresh()->update([
            'status' => ReportExport::STATUS_FAILED,
            'error_message' => $exception?->getMessage() ?? 'Report export job failed.',
        ]);
    }
}
