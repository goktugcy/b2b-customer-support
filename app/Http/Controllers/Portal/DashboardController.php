<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetricsService $metrics): Response
    {
        return Inertia::render('Portal/Dashboard', [
            'metrics' => $metrics->portal(request()->user()->company),
        ]);
    }
}
