<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Services\Tickets\TicketBulkActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketBulkController extends Controller
{
    public function __invoke(Request $request, TicketBulkActionService $bulk): RedirectResponse
    {
        $this->authorize('bulkUpdate', \App\Models\Ticket::class);

        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1', 'max:100'],
            'ticket_ids.*' => ['string', 'exists:tickets,public_id'],
            'status' => ['nullable', Rule::in(array_map(fn (TicketStatus $status) => $status->value, TicketStatus::cases()))],
            'priority' => ['nullable', Rule::in(array_map(fn (TicketPriority $priority) => $priority->value, TicketPriority::cases()))],
            'assigned_to_user_id' => ['nullable', 'exists:users,public_id'],
            'tag_names' => ['nullable', 'array', 'max:20'],
            'tag_names.*' => ['string', 'max:40'],
        ]);

        $count = $bulk->updateForProvider($request->user(), $validated, $request);

        return back()->with('success', "{$count} ticket(s) updated.");
    }
}
