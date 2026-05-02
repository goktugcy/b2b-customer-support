<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function download(TicketAttachment $ticketAttachment): StreamedResponse
    {
        $this->authorize('view', $ticketAttachment);

        return Storage::disk($ticketAttachment->disk)->download($ticketAttachment->path, $ticketAttachment->original_name);
    }
}
