<?php

namespace App\Services\Reports;

use App\Events\ReportExportUpdated;
use App\Jobs\GenerateReportExport;
use App\Models\ApiClient;
use App\Models\ReportExport;
use App\Models\Ticket;
use App\Models\TicketCsatSurvey;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function queue(User|ApiClient $actor, Request $request, string $type, string $format): ReportExport
    {
        $export = ReportExport::create([
            'company_id' => $actor->company_id,
            'requested_by_user_id' => $actor instanceof User ? $actor->id : null,
            'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
            'type' => $type,
            'format' => $format,
            'filters' => $this->exportFilters($request),
            'status' => ReportExport::STATUS_PENDING,
            'disk' => 'local',
            'expires_at' => now()->addDays(14),
        ]);

        GenerateReportExport::dispatchAfterResponse($export);

        return $export;
    }

    public function recoverStaleExports(User|ApiClient $actor): void
    {
        ReportExport::query()
            ->when(
                $actor instanceof User,
                fn (Builder $query) => $query->where('requested_by_user_id', $actor->id),
                fn (Builder $query) => $query->where('api_client_id', $actor->id),
            )
            ->where(function (Builder $query): void {
                $query->where('status', ReportExport::STATUS_PENDING)
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('status', ReportExport::STATUS_PROCESSING)
                            ->where('updated_at', '<=', now()->subMinutes(10));
                    });
            })
            ->latest()
            ->limit(5)
            ->get()
            ->each(fn (ReportExport $export) => GenerateReportExport::dispatchAfterResponse($export));
    }

    public function generate(ReportExport $export): void
    {
        $actor = $export->requestedBy ?: $export->apiClient;

        if (! $actor) {
            $export->update([
                'status' => ReportExport::STATUS_FAILED,
                'error_message' => 'The report requester no longer exists.',
            ]);

            return;
        }

        $export->update([
            'status' => ReportExport::STATUS_PROCESSING,
            'error_message' => null,
        ]);

        try {
            $request = Request::create('/report-export', 'GET', $export->filters ?? []);
            $filename = $export->type.'-report-'.$export->public_id.'.'.$export->format;
            $path = 'report-exports/'.$filename;

            $contents = match ($export->type.'.'.$export->format) {
                'tickets.csv' => $this->ticketsCsvContents($actor, $request),
                'tickets.pdf' => $this->ticketsPdfContents($actor, $request),
                'csat.csv' => $this->csatCsvContents($actor, $request),
                'csat.pdf' => $this->csatPdfContents($actor, $request),
                default => throw new \InvalidArgumentException('Unsupported report export type.'),
            };

            Storage::disk($export->disk)->put($path, $contents);

            $export->update([
                'status' => ReportExport::STATUS_COMPLETED,
                'path' => $path,
                'filename' => $filename,
                'completed_at' => now(),
            ]);
            ReportExportUpdated::dispatch($export->refresh());
        } catch (\Throwable $exception) {
            $export->update([
                'status' => ReportExport::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
            ReportExportUpdated::dispatch($export->refresh());
        }
    }

    public function download(ReportExport $export): StreamedResponse
    {
        abort_unless($export->status === ReportExport::STATUS_COMPLETED && $export->path, 404);
        abort_unless(Storage::disk($export->disk)->exists($export->path), 404);

        return Storage::disk($export->disk)->download($export->path, $export->filename);
    }

    public function ticketsCsv(User|ApiClient $actor, Request $request): StreamedResponse
    {
        return response()->streamDownload(function () use ($actor, $request): void {
            echo $this->ticketsCsvContents($actor, $request);
        }, 'tickets-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function csatCsv(User|ApiClient $actor, Request $request): StreamedResponse
    {
        return response()->streamDownload(function () use ($actor, $request): void {
            echo $this->csatCsvContents($actor, $request);
        }, 'csat-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function ticketsPdf(User|ApiClient $actor, Request $request): Response
    {
        return response($this->ticketsPdfContents($actor, $request), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="tickets-report.pdf"',
        ]);
    }

    public function csatPdf(User|ApiClient $actor, Request $request): Response
    {
        return response($this->csatPdfContents($actor, $request), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="csat-report.pdf"',
        ]);
    }

    public function ticketsQuery(User|ApiClient $actor, Request $request): Builder
    {
        return Ticket::query()
            ->visibleTo($actor)
            ->with(['company', 'supportProject', 'tracker', 'assignee', 'tags'])
            ->when($request->query('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->query('priority'), fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($request->query('from'), fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->when($request->query('company'), function (Builder $query, string $company): void {
                $query->whereHas('company', fn (Builder $companyQuery) => $companyQuery
                    ->where('public_id', $company)
                    ->orWhere('slug', $company));
            })
            ->when($request->query('project'), function (Builder $query, string $project): void {
                $query->whereHas('supportProject', fn (Builder $projectQuery) => $projectQuery
                    ->where('public_id', $project)
                    ->orWhere('slug', $project));
            })
            ->when($request->query('tracker'), function (Builder $query, string $tracker): void {
                $query->whereHas('tracker', fn (Builder $trackerQuery) => $trackerQuery
                    ->where('public_id', $tracker)
                    ->orWhere('slug', $tracker));
            })
            ->when($request->query('tag'), function (Builder $query, string $tag): void {
                $query->whereHas('tags', fn (Builder $tagQuery) => $tagQuery
                    ->where('public_id', $tag)
                    ->orWhere('slug', $tag)
                    ->orWhere('name', $tag));
            })
            ->when($request->query('assignee'), function (Builder $query, string $assignee): void {
                $query->whereHas('assignee', fn (Builder $userQuery) => $userQuery->where('public_id', $assignee));
            })
            ->when($request->query('sla'), fn (Builder $query, string $sla) => $this->applySlaFilter($query, $sla))
            ->latest();
    }

    public function csatQuery(User|ApiClient $actor, Request $request): Builder
    {
        return TicketCsatSurvey::query()
            ->whereHas('ticket', fn (Builder $query) => $query->visibleTo($actor))
            ->with('ticket.company')
            ->when($request->query('company'), function (Builder $query, string $company): void {
                $query->whereHas('ticket.company', fn (Builder $companyQuery) => $companyQuery
                    ->where('public_id', $company)
                    ->orWhere('slug', $company));
            })
            ->when($request->query('rating'), fn (Builder $query, string $rating) => $query->where('rating', (int) $rating))
            ->when($request->query('from'), fn (Builder $query, string $from) => $query->whereDate('responded_at', '>=', $from))
            ->when($request->query('to'), fn (Builder $query, string $to) => $query->whereDate('responded_at', '<=', $to))
            ->latest();
    }

    private function ticketsCsvContents(User|ApiClient $actor, Request $request): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'Company', 'Subject', 'Status', 'Priority', 'Project', 'Tracker', 'Assignee', 'Tags', 'Created At']);

        $this->ticketsQuery($actor, $request)
            ->each(function (Ticket $ticket) use ($handle): void {
                fputcsv($handle, [
                    $ticket->displayId(),
                    $ticket->company?->name,
                    $ticket->subject,
                    $ticket->status->value,
                    $ticket->priority->value,
                    $ticket->supportProject?->name,
                    $ticket->tracker?->name,
                    $ticket->assignee?->name,
                    $ticket->tags->pluck('name')->implode(', '),
                    $ticket->created_at?->toDateTimeString(),
                ]);
            });

        rewind($handle);

        return stream_get_contents($handle) ?: '';
    }

    private function csatCsvContents(User|ApiClient $actor, Request $request): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Survey ID', 'Ticket ID', 'Company', 'Subject', 'Rating', 'Comment', 'Responded At']);

        $this->csatQuery($actor, $request)
            ->each(function (TicketCsatSurvey $survey) use ($handle): void {
                fputcsv($handle, [
                    $survey->public_id,
                    $survey->ticket?->displayId(),
                    $survey->ticket?->company?->name,
                    $survey->ticket?->subject,
                    $survey->rating,
                    $survey->comment,
                    $survey->responded_at?->toDateTimeString(),
                ]);
            });

        rewind($handle);

        return stream_get_contents($handle) ?: '';
    }

    private function ticketsPdfContents(User|ApiClient $actor, Request $request): string
    {
        $tickets = $this->ticketsQuery($actor, $request)
            ->limit(500)
            ->get();

        return Pdf::loadHTML($this->ticketsHtml($tickets))->output();
    }

    private function csatPdfContents(User|ApiClient $actor, Request $request): string
    {
        $surveys = $this->csatQuery($actor, $request)
            ->limit(500)
            ->get();

        return Pdf::loadHTML($this->csatHtml($surveys))->output();
    }

    private function ticketsHtml($tickets): string
    {
        $rows = $tickets->map(fn (Ticket $ticket): string => sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            e($ticket->displayId()),
            e($ticket->company?->name ?? ''),
            e($ticket->subject),
            e($ticket->status->value),
            e($ticket->priority->value),
            e($ticket->assignee?->name ?? ''),
        ))->implode('');

        return '<h1>Tickets report</h1><table width="100%" border="1" cellspacing="0" cellpadding="6"><thead><tr><th>ID</th><th>Company</th><th>Subject</th><th>Status</th><th>Priority</th><th>Assignee</th></tr></thead><tbody>'.$rows.'</tbody></table>';
    }

    private function csatHtml($surveys): string
    {
        $rows = $surveys->map(fn (TicketCsatSurvey $survey): string => sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            e($survey->public_id),
            e(($survey->ticket?->displayId() ?? '').' '.$survey->ticket?->subject),
            e((string) $survey->rating),
            e($survey->comment ?? ''),
        ))->implode('');

        return '<h1>CSAT report</h1><table width="100%" border="1" cellspacing="0" cellpadding="6"><thead><tr><th>ID</th><th>Ticket</th><th>Rating</th><th>Comment</th></tr></thead><tbody>'.$rows.'</tbody></table>';
    }

    private function exportFilters(Request $request): array
    {
        return $request->only([
            'status',
            'priority',
            'company',
            'project',
            'tracker',
            'tag',
            'from',
            'to',
            'sla',
            'assignee',
            'rating',
        ]);
    }

    private function applySlaFilter(Builder $query, string $sla): Builder
    {
        return match ($sla) {
            'first_response_breached' => $query->whereNotNull('sla_first_response_breached_at'),
            'resolution_breached' => $query->whereNotNull('sla_resolution_breached_at'),
            'breached' => $query->where(fn (Builder $scope) => $scope
                ->whereNotNull('sla_first_response_breached_at')
                ->orWhereNotNull('sla_resolution_breached_at')),
            'due_soon' => $query->whereBetween('due_at', [now(), now()->addDay()]),
            default => $query,
        };
    }
}
