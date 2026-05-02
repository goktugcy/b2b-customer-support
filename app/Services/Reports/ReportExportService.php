<?php

namespace App\Services\Reports;

use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\TicketCsatSurvey;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function ticketsCsv(User|ApiClient $actor, Request $request): StreamedResponse
    {
        return response()->streamDownload(function () use ($actor, $request): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Company', 'Subject', 'Status', 'Priority', 'Assignee', 'Created At']);

            $this->ticketsQuery($actor, $request)
                ->with(['company', 'assignee'])
                ->each(function (Ticket $ticket) use ($handle): void {
                    fputcsv($handle, [
                        $ticket->displayId(),
                        $ticket->company?->name,
                        $ticket->subject,
                        $ticket->status->value,
                        $ticket->priority->value,
                        $ticket->assignee?->name,
                        $ticket->created_at?->toDateTimeString(),
                    ]);
                });

            fclose($handle);
        }, 'tickets-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function csatCsv(User|ApiClient $actor, Request $request): StreamedResponse
    {
        return response()->streamDownload(function () use ($actor, $request): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Survey ID', 'Ticket ID', 'Subject', 'Rating', 'Comment', 'Responded At']);

            $this->csatQuery($actor, $request)
                ->with('ticket')
                ->each(function (TicketCsatSurvey $survey) use ($handle): void {
                    fputcsv($handle, [
                        $survey->public_id,
                        $survey->ticket?->displayId(),
                        $survey->ticket?->subject,
                        $survey->rating,
                        $survey->comment,
                        $survey->responded_at?->toDateTimeString(),
                    ]);
                });

            fclose($handle);
        }, 'csat-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function ticketsPdf(User|ApiClient $actor, Request $request): Response
    {
        $tickets = $this->ticketsQuery($actor, $request)
            ->with(['company', 'assignee'])
            ->limit(500)
            ->get();

        return Pdf::loadHTML($this->ticketsHtml($tickets))
            ->download('tickets-report.pdf');
    }

    public function csatPdf(User|ApiClient $actor, Request $request): Response
    {
        $surveys = $this->csatQuery($actor, $request)
            ->with('ticket')
            ->limit(500)
            ->get();

        return Pdf::loadHTML($this->csatHtml($surveys))
            ->download('csat-report.pdf');
    }

    public function ticketsQuery(User|ApiClient $actor, Request $request): Builder
    {
        return Ticket::query()
            ->visibleTo($actor)
            ->when($request->query('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->query('priority'), fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($request->query('from'), fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->latest();
    }

    public function csatQuery(User|ApiClient $actor, Request $request): Builder
    {
        return TicketCsatSurvey::query()
            ->whereHas('ticket', fn (Builder $query) => $query->visibleTo($actor))
            ->when($request->query('from'), fn (Builder $query, string $from) => $query->whereDate('responded_at', '>=', $from))
            ->when($request->query('to'), fn (Builder $query, string $to) => $query->whereDate('responded_at', '<=', $to))
            ->latest();
    }

    private function ticketsHtml($tickets): string
    {
        $rows = $tickets->map(fn (Ticket $ticket): string => sprintf(
            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
            e($ticket->displayId()),
            e($ticket->company?->name ?? ''),
            e($ticket->subject),
            e($ticket->status->value),
            e($ticket->priority->value),
        ))->implode('');

        return '<h1>Tickets report</h1><table width="100%" border="1" cellspacing="0" cellpadding="6"><thead><tr><th>ID</th><th>Company</th><th>Subject</th><th>Status</th><th>Priority</th></tr></thead><tbody>'.$rows.'</tbody></table>';
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
}
