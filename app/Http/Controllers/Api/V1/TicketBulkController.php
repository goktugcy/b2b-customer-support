<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Services\Tickets\TicketBulkActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketBulkController extends Controller
{
    public function __invoke(Request $request, TicketBulkActionService $bulk): JsonResponse
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1', 'max:100'],
            'ticket_ids.*' => ['string', 'exists:tickets,public_id'],
            'status' => ['nullable', Rule::in(['resolved', 'closed'])],
            'priority' => ['nullable', Rule::in(array_map(fn (TicketPriority $priority) => $priority->value, TicketPriority::cases()))],
            'tag_names' => ['nullable', 'array', 'max:20'],
            'tag_names.*' => ['string', 'max:40'],
        ]);

        return response()->json([
            'updated' => $bulk->updateForApi($request->user(), $validated, $request),
        ]);
    }
}
