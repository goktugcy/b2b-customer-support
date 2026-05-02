<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function ticketsCsv(Request $request, ReportExportService $reports): Response
    {
        abort_unless($request->user()->tokenCan('reports:read'), 403);

        return $reports->ticketsCsv($request->user(), $request);
    }

    public function ticketsPdf(Request $request, ReportExportService $reports): Response
    {
        abort_unless($request->user()->tokenCan('reports:read'), 403);

        return $reports->ticketsPdf($request->user(), $request);
    }

    public function csatCsv(Request $request, ReportExportService $reports): Response
    {
        abort_unless($request->user()->tokenCan('reports:read'), 403);

        return $reports->csatCsv($request->user(), $request);
    }

    public function csatPdf(Request $request, ReportExportService $reports): Response
    {
        abort_unless($request->user()->tokenCan('reports:read'), 403);

        return $reports->csatPdf($request->user(), $request);
    }
}
