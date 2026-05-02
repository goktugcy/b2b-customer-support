<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TicketSavedView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketSavedViewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('tickets:read'), 403);

        return response()->json([
            'data' => TicketSavedView::query()
                ->where('company_id', $request->user()->company_id)
                ->where('section', TicketSavedView::SECTION_PORTAL)
                ->where('is_shared', true)
                ->orderBy('name')
                ->get()
                ->map(fn (TicketSavedView $view): array => $this->payload($view)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('tickets:read'), 403);

        $validated = $request->validate($this->rules());
        $ownerId = $request->user()->created_by_user_id
            ?? \App\Models\User::query()->where('company_id', $request->user()->company_id)->value('id');

        abort_unless($ownerId, 422, 'API client needs an owner user to create shared views.');

        $view = TicketSavedView::create([
            'company_id' => $request->user()->company_id,
            'user_id' => $ownerId,
            'section' => TicketSavedView::SECTION_PORTAL,
            'name' => $validated['name'],
            'filters' => $validated['filters'] ?? [],
            'columns' => $validated['columns'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'is_shared' => true,
            'is_default' => false,
        ]);

        return response()->json(['data' => $this->payload($view)], 201);
    }

    public function update(Request $request, TicketSavedView $ticketView): JsonResponse
    {
        abort_unless($request->user()->tokenCan('tickets:read'), 403);
        abort_unless($ticketView->company_id === $request->user()->company_id && $ticketView->is_shared, 403);

        $validated = $request->validate($this->rules(partial: true));
        $ticketView->update($validated + ['is_shared' => true]);

        return response()->json(['data' => $this->payload($ticketView->refresh())]);
    }

    public function destroy(Request $request, TicketSavedView $ticketView): JsonResponse
    {
        abort_unless($request->user()->tokenCan('tickets:read'), 403);
        abort_unless($ticketView->company_id === $request->user()->company_id && $ticketView->is_shared, 403);

        $ticketView->delete();

        return response()->json(status: 204);
    }

    private function rules(bool $partial = false): array
    {
        return [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:120'],
            'filters' => ['nullable', 'array'],
            'columns' => ['nullable', 'array'],
            'sort' => ['nullable', 'array'],
        ];
    }

    private function payload(TicketSavedView $view): array
    {
        return [
            'id' => $view->public_id,
            'name' => $view->name,
            'filters' => $view->filters ?? [],
            'columns' => $view->columns,
            'sort' => $view->sort,
        ];
    }
}
