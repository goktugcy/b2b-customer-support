<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Ticket;
use App\Services\Tickets\TicketMergeSplitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketMergeSplitController extends Controller
{
    public function merge(Request $request, Company $company, Ticket $ticket, TicketMergeSplitService $service): RedirectResponse
    {
        $this->authorize('merge', $ticket);

        $validated = $request->validate([
            'target_ticket_id' => ['required', 'string', 'max:64'],
        ]);

        $targetInput = ltrim($validated['target_ticket_id'], '#');
        $target = Ticket::query()
            ->where('company_id', $ticket->company_id)
            ->where(function ($query) use ($targetInput): void {
                if (ctype_digit($targetInput)) {
                    $query->where('ticket_number', (int) $targetInput);
                }

                $query->orWhere('public_id', $targetInput);
            })
            ->firstOrFail();

        $service->merge($ticket, $target, $request->user(), $request);

        return redirect()->route('admin.tickets.show', $target->adminRouteParameters())->with('success', 'Ticket merged.');
    }

    public function split(Request $request, Company $company, Ticket $ticket, TicketMergeSplitService $service): RedirectResponse
    {
        $this->authorize('split', $ticket);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'comment_ids' => ['required', 'array', 'min:1'],
            'comment_ids.*' => ['string', 'exists:ticket_comments,public_id'],
        ]);

        $newTicket = $service->split($ticket, $request->user(), $validated['subject'], $validated['comment_ids'], $request);

        return redirect()->route('admin.tickets.show', $newTicket->adminRouteParameters())->with('success', 'Ticket split created.');
    }
}
