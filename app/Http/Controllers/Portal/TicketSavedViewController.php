<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\TicketSavedView;
use App\Services\Tickets\TicketSavedViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketSavedViewController extends Controller
{
    public function store(Request $request, TicketSavedViewService $views): RedirectResponse
    {
        abort_unless($request->user()->can('ticket_views.manage'), 403);

        $views->store($request->user(), TicketSavedView::SECTION_PORTAL, $this->validated($request));

        return back()->with('success', 'Saved view created.');
    }

    public function update(Request $request, TicketSavedView $ticketView, TicketSavedViewService $views): RedirectResponse
    {
        abort_unless($request->user()->can('ticket_views.manage'), 403);

        $views->update($ticketView, $request->user(), $this->validated($request, partial: true));

        return back()->with('success', 'Saved view updated.');
    }

    public function destroy(Request $request, TicketSavedView $ticketView, TicketSavedViewService $views): RedirectResponse
    {
        abort_unless($request->user()->can('ticket_views.manage'), 403);

        $views->delete($ticketView, $request->user());

        return back()->with('success', 'Saved view deleted.');
    }

    private function validated(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'filters' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'sort' => ['nullable', 'array'],
            'is_shared' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }
}
