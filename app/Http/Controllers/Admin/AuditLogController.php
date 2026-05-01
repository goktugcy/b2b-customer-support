<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('audit.view'), 403);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => AuditLog::query()
                ->with(['company', 'actor', 'apiClient'])
                ->latest('created_at')
                ->paginate(25)
                ->through(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'company' => $log->company?->name,
                    'actor' => $log->actor?->name ?? $log->apiClient?->name,
                    'action' => $log->action,
                    'before' => $log->before,
                    'after' => $log->after,
                    'created_at' => $log->created_at?->toISOString(),
                ]),
        ]);
    }
}
