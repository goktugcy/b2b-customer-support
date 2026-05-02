<?php

namespace App\Services\Tickets;

use App\Enums\TicketVisibility;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TicketAttachmentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TicketEventRecorder $events,
    ) {}

    public function store(
        Ticket $ticket,
        UploadedFile $file,
        User|ApiClient|null $actor,
        TicketVisibility $visibility = TicketVisibility::Public,
        ?Request $request = null,
        ?TicketComment $comment = null,
    ): TicketAttachment {
        $this->validateFile($file);

        return DB::transaction(function () use ($ticket, $file, $actor, $visibility, $request, $comment): TicketAttachment {
            $directory = 'tickets/'.$ticket->company->public_id.'/'.$ticket->public_id;
            $path = $file->store($directory, 'local');
            $absolutePath = Storage::disk('local')->path($path);
            $checksum = is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null;

            $attachment = TicketAttachment::create([
                'company_id' => $ticket->company_id,
                'ticket_id' => $ticket->id,
                'comment_id' => $comment?->id,
                'uploaded_by_user_id' => $actor instanceof User ? $actor->id : null,
                'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
                'checksum' => $checksum,
                'visibility' => $visibility,
                'metadata' => [],
            ]);

            $this->events->record(
                ticket: $ticket,
                eventType: 'ticket.attachment.created',
                actor: $actor,
                newValues: [
                    'attachment_id' => $attachment->public_id,
                    'comment_id' => $comment?->public_id,
                    'filename' => $attachment->original_name,
                    'visibility' => $visibility->value,
                ],
                request: $request,
            );

            $this->audit->log('ticket.attachment.created', $ticket, $actor, after: [
                'attachment_id' => $attachment->public_id,
                'comment_id' => $comment?->public_id,
                'filename' => $attachment->original_name,
            ], request: $request);

            return $attachment;
        });
    }

    private function validateFile(UploadedFile $file): void
    {
        $maxKilobytes = (int) config('support.attachments.max_kilobytes', 20480);
        $allowedMimes = config('support.attachments.allowed_mimes', []);
        $allowedExtensions = array_map('strtolower', config('support.attachments.allowed_extensions', []));
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType() ?: $file->getClientMimeType();

        $errors = [];

        if (($file->getSize() ?: 0) > $maxKilobytes * 1024) {
            $errors[] = 'The file is too large.';
        }

        if ($allowedExtensions && ! in_array($extension, $allowedExtensions, true)) {
            $errors[] = 'This file extension is not allowed.';
        }

        if ($allowedMimes && ! in_array($mime, $allowedMimes, true)) {
            $errors[] = 'This file type is not allowed.';
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => implode(' ', $errors)]);
        }
    }
}
