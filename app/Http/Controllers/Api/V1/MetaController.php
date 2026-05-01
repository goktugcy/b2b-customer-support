<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function ticketOptions(): JsonResponse
    {
        return response()->json([
            'statuses' => array_map(fn (TicketStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ], TicketStatus::cases()),
            'priorities' => array_map(fn (TicketPriority $priority): array => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ], TicketPriority::cases()),
        ]);
    }
}
