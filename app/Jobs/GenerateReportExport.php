<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\Reports\ReportExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ReportExport $export) {}

    public function handle(ReportExportService $reports): void
    {
        $reports->generate($this->export->refresh());
    }
}
