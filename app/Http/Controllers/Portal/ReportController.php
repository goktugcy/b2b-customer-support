<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        return Inertia::render('Portal/Reports/Index', [
            'filters' => $request->only(['status', 'priority', 'from', 'to']),
        ]);
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
}
