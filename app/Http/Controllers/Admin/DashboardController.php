<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetricsService $metrics): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'metrics' => $metrics->admin(request()->user()),
        ]);
    }
}
