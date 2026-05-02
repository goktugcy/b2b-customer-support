<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportExport;
use App\Services\Csat\CsatService;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    public function index(Request $request, CsatService $csat): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return Inertia::render('Admin/Reports/Index', [
            'filters' => $request->only(['status', 'priority', 'company', 'project', 'tracker', 'tag', 'from', 'to', 'sla', 'assignee', 'rating']),
            'exports' => $this->exportsPayload($request),
            'csatSummary' => $csat->summaryFor($request->user()),
        ]);
    }

    public function storeExport(Request $request, ReportExportService $reports): RedirectResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['tickets', 'csat'])],
            'format' => ['required', Rule::in(['csv', 'pdf'])],
        ]);

        $reports->queue($request->user(), $request, $validated['type'], $validated['format']);

        return back()->with('success', 'Report export queued.');
    }

    public function download(Request $request, ReportExport $reportExport, ReportExportService $reports): SymfonyResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);
        abort_unless($reportExport->requested_by_user_id === $request->user()->id, 403);

        return $reports->download($reportExport);
    }

    public function ticketsCsv(Request $request, ReportExportService $reports): SymfonyResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return $reports->ticketsCsv($request->user(), $request);
    }

    public function ticketsPdf(Request $request, ReportExportService $reports): SymfonyResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return $reports->ticketsPdf($request->user(), $request);
    }

    public function csatCsv(Request $request, ReportExportService $reports): SymfonyResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return $reports->csatCsv($request->user(), $request);
    }

    public function csatPdf(Request $request, ReportExportService $reports): SymfonyResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return $reports->csatPdf($request->user(), $request);
    }

    private function exportsPayload(Request $request): array
    {
        return ReportExport::query()
            ->where('requested_by_user_id', $request->user()->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (ReportExport $export): array => [
                'id' => $export->public_id,
                'type' => $export->type,
                'format' => $export->format,
                'status' => $export->status,
                'filters' => $export->filters,
                'error_message' => $export->error_message,
                'created_at' => $export->created_at?->toISOString(),
                'completed_at' => $export->completed_at?->toISOString(),
                'download_url' => $export->status === ReportExport::STATUS_COMPLETED
                    ? route('admin.reports.exports.download', $export, absolute: false)
                    : null,
            ])
            ->all();
    }
}
