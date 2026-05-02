<?php

namespace App\Services\InboundEmail;

use App\Enums\TicketSource;
use App\Enums\TicketVisibility;
use App\Models\Company;
use App\Models\InboundEmailMessage;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketCommentService;
use App\Services\Tickets\TicketCreationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InboundEmailService
{
    public function __construct(
        private readonly TicketCreationService $tickets,
        private readonly TicketCommentService $comments,
    ) {}

    public function ingest(string $provider, Request $request): InboundEmailMessage
    {
        $this->validateSecret($request);

        $parsed = $this->parse($provider, $request);

        if ($parsed['message_id']) {
            $existing = InboundEmailMessage::query()
                ->where('provider', $provider)
                ->where('message_id', $parsed['message_id'])
                ->first();

            if ($existing) {
                $existing->update(['status' => InboundEmailMessage::STATUS_DUPLICATE]);

                return $existing;
            }
        }

        $message = InboundEmailMessage::create([
            'provider' => $provider,
            'message_id' => $parsed['message_id'],
            'from_email' => $parsed['from_email'],
            'from_name' => $parsed['from_name'],
            'subject' => $parsed['subject'],
            'raw_payload' => $parsed['raw_payload'],
            'parsed_body' => $parsed['body'],
            'status' => InboundEmailMessage::STATUS_PENDING,
        ]);

        try {
            $this->process($message, $request);
        } catch (\Throwable $exception) {
            $message->update([
                'status' => InboundEmailMessage::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        }

        return $message->refresh();
    }

    private function process(InboundEmailMessage $message, Request $request): void
    {
        DB::transaction(function () use ($message, $request): void {
            $sender = $this->sender($message);
            $company = $this->company($message, $request, $sender);

            if (! $company) {
                throw new \RuntimeException('Inbound email could not be matched to a company.');
            }

            $ticket = $this->ticket($company, $message);
            $files = $this->uploadedFiles($request);

            if ($ticket) {
                $comment = $this->comments->create(
                    ticket: $ticket,
                    actor: $sender,
                    body: $message->parsed_body ?: $message->subject ?: 'Email reply',
                    visibility: TicketVisibility::Public,
                    request: $request,
                    files: $files,
                );

                $message->update([
                    'company_id' => $company->id,
                    'ticket_id' => $ticket->id,
                    'status' => InboundEmailMessage::STATUS_PROCESSED,
                    'error_message' => null,
                    'raw_payload' => array_merge($message->raw_payload ?? [], ['comment_id' => $comment->public_id]),
                ]);

                return;
            }

            $targets = $this->defaultTargets();

            if ($targets['target_department_ids'] === [] && $targets['target_user_ids'] === []) {
                throw new \RuntimeException('Inbound email could not be routed because no provider target exists.');
            }

            $ticket = $this->tickets->create([
                'company_id' => $company->id,
                'subject' => $message->subject ?: 'Inbound email',
                'description' => $message->parsed_body ?: $message->subject ?: 'Inbound email',
                'target_department_ids' => $targets['target_department_ids'],
                'target_user_ids' => $targets['target_user_ids'],
                'attachments' => $files,
            ], $sender, TicketSource::Email, $request);

            $message->update([
                'company_id' => $company->id,
                'ticket_id' => $ticket->id,
                'status' => InboundEmailMessage::STATUS_PROCESSED,
                'error_message' => null,
            ]);
        });
    }

    private function parse(string $provider, Request $request): array
    {
        $payload = $request->except(array_keys($request->allFiles()));

        $messageId = $this->first($payload, [
            'message_id',
            'Message-Id',
            'MessageID',
            'message-id',
            'headers.message-id',
            'Headers.Message-Id',
        ]);

        $from = $this->first($payload, [
            'from_email',
            'sender',
            'from',
            'From',
            'FromFull.Email',
            'from.email',
        ]);

        return [
            'provider' => $provider,
            'message_id' => $messageId ? trim((string) $messageId) : null,
            'from_email' => $this->extractEmail($from),
            'from_name' => $this->first($payload, ['from_name', 'FromName', 'FromFull.Name', 'from.name']),
            'subject' => $this->first($payload, ['subject', 'Subject']),
            'body' => $this->first($payload, ['body', 'body-plain', 'stripped-text', 'TextBody', 'text', 'HtmlBody']),
            'raw_payload' => [
                'provider' => $provider,
                'payload' => $payload,
                'attachments_count' => count($this->uploadedFiles($request)),
            ],
        ];
    }

    private function sender(InboundEmailMessage $message): ?User
    {
        if (! $message->from_email) {
            return null;
        }

        return User::query()
            ->where('email', $message->from_email)
            ->first();
    }

    private function company(InboundEmailMessage $message, Request $request, ?User $sender): ?Company
    {
        $companyId = $request->input('company_id') ?: $request->input('company');
        $companySlug = $request->input('company_slug') ?: config('support.inbound_email.default_company');

        if ($companyId || $companySlug) {
            return Company::query()
                ->when($companyId, fn ($query) => $query->where('public_id', $companyId))
                ->when(! $companyId && $companySlug, fn ($query) => $query->where('slug', $companySlug))
                ->first()
                ?: $sender?->company;
        }

        return $sender?->company;
    }

    private function ticket(Company $company, InboundEmailMessage $message): ?Ticket
    {
        $haystack = ($message->subject ?? '').' '.$message->parsed_body;

        if (! preg_match('/#?(\d{5,})/', $haystack, $matches)) {
            return null;
        }

        return Ticket::query()
            ->where('company_id', $company->id)
            ->where('ticket_number', (int) $matches[1])
            ->first();
    }

    private function defaultTargets(): array
    {
        $department = SupportDepartment::query()
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->where('type', 'provider'))
            ->orderBy('name')
            ->first();

        if ($department) {
            return [
                'target_department_ids' => [$department->public_id],
                'target_user_ids' => [],
            ];
        }

        $user = User::query()
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->where('type', 'provider'))
            ->orderBy('name')
            ->first();

        return [
            'target_department_ids' => [],
            'target_user_ids' => $user ? [$user->public_id] : [],
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedFiles(Request $request): array
    {
        return collect($request->allFiles())
            ->flatten()
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->values()
            ->all();
    }

    private function first(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractEmail(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $value, $matches)) {
            return Str::lower($matches[0]);
        }

        return null;
    }

    private function validateSecret(Request $request): void
    {
        $secret = (string) config('support.inbound_email.secret');

        if ($secret === '') {
            return;
        }

        $provided = $request->header('X-Support-Inbound-Secret') ?: $request->query('secret');

        if (! hash_equals($secret, (string) $provided)) {
            throw new HttpException(403, 'Invalid inbound email signature.');
        }
    }
}
