<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('audit.view'), 403);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $this->query($request)
                ->latest('created_at')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'company' => $log->company?->name,
                    'actor' => $log->actor?->name ?? $log->apiClient?->name,
                    'action' => $log->action,
                    'before' => $log->before,
                    'after' => $log->after,
                    'metadata' => $log->metadata,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at?->toISOString(),
                ]),
            'filters' => $request->only(['action', 'company', 'actor', 'from', 'to']),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('audit.view'), 403);

        return response()->streamDownload(function () use ($request): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Action', 'Company', 'Actor', 'Auditable', 'Before', 'After', 'IP', 'Created At']);

            $this->query($request)
                ->latest('created_at')
                ->each(function (AuditLog $log) use ($handle): void {
                    fputcsv($handle, [
                        $log->id,
                        $log->action,
                        $log->company?->name,
                        $log->actor?->name ?? $log->apiClient?->name,
                        trim(($log->auditable_type ?? '').' '.$log->auditable_id),
                        json_encode($log->before),
                        json_encode($log->after),
                        $log->ip_address,
                        $log->created_at?->toDateTimeString(),
                    ]);
                });

            fclose($handle);
        }, 'audit-logs.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request): Builder
    {
        return AuditLog::query()
            ->with(['company', 'actor', 'apiClient'])
            ->when($request->query('action'), fn (Builder $query, string $action) => $query->where('action', 'like', '%'.$action.'%'))
            ->when($request->query('company'), function (Builder $query, string $company): void {
                $query->whereHas('company', fn (Builder $companyQuery) => $companyQuery
                    ->where('public_id', $company)
                    ->orWhere('slug', $company)
                    ->orWhere('name', 'like', '%'.$company.'%'));
            })
            ->when($request->query('actor'), function (Builder $query, string $actor): void {
                $query->where(function (Builder $scope) use ($actor): void {
                    $scope->whereHas('actor', fn (Builder $actorQuery) => $actorQuery
                        ->where('name', 'like', '%'.$actor.'%')
                        ->orWhere('email', 'like', '%'.$actor.'%'))
                        ->orWhereHas('apiClient', fn (Builder $apiQuery) => $apiQuery->where('name', 'like', '%'.$actor.'%'));
                });
            })
            ->when($request->query('from'), fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to));
    }
}
