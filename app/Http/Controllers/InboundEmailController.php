<?php

namespace App\Http\Controllers;

use App\Models\InboundEmailMessage;
use App\Services\InboundEmail\InboundEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundEmailController extends Controller
{
    public function __invoke(string $provider, Request $request, InboundEmailService $inboundEmail): JsonResponse
    {
        abort_unless(in_array($provider, ['mailgun', 'postmark', 'sendgrid', 'generic'], true), 404);

        $message = $inboundEmail->ingest($provider, $request);

        return response()->json([
            'id' => $message->public_id,
            'status' => $message->status,
            'ticket_id' => $message->ticket?->public_id,
            'display_id' => $message->ticket?->displayId(),
            'duplicate' => $message->status === InboundEmailMessage::STATUS_DUPLICATE,
        ], $message->status === InboundEmailMessage::STATUS_FAILED ? 422 : 202);
    }
}
