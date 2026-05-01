<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Tickets\IssueTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    public function ticketOptions(Request $request, IssueTrackingService $issueTracking): JsonResponse
    {
        $company = Company::query()->find($request->user()->company_id);

        return response()->json([
            'statuses' => array_map(fn (TicketStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ], TicketStatus::cases()),
            'priorities' => array_map(fn (TicketPriority $priority): array => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ], TicketPriority::cases()),
            'projects' => $company ? $issueTracking->projectOptions($company) : [],
            'trackers' => $issueTracking->trackerOptions(),
            'categories' => $company ? $issueTracking->categoryOptions($company) : [],
            'tags' => $issueTracking->tagOptions(),
            'custom_fields' => $issueTracking->customFieldOptions(),
        ]);
    }
}
