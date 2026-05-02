<?php

namespace App\Services\CannedResponses;

use App\Models\CannedResponse;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Collection;

class CannedResponseService
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /**
     * @return Collection<int, CannedResponse>
     */
    public function forUser(User $user): Collection
    {
        return CannedResponse::query()
            ->visibleTo($user)
            ->orderBy('title')
            ->get();
    }

    public function allForAdmin(User $user): Collection
    {
        return CannedResponse::query()
            ->where(function ($query) use ($user): void {
                $query->where('scope', CannedResponse::SCOPE_GLOBAL)
                    ->orWhere('user_id', $user->id);
            })
            ->with('user:id,public_id,name')
            ->latest()
            ->get();
    }

    public function store(User $user, array $data): CannedResponse
    {
        $scope = $data['scope'] ?? CannedResponse::SCOPE_PERSONAL;

        return CannedResponse::create([
            'user_id' => $scope === CannedResponse::SCOPE_PERSONAL ? $user->id : null,
            'scope' => $scope,
            'title' => $data['title'],
            'shortcut' => $data['shortcut'] ?? null,
            'body' => $this->sanitizer->sanitize($data['body']),
            'variables' => $data['variables'] ?? [],
            'status' => $data['status'],
        ]);
    }

    public function update(CannedResponse $response, User $user, array $data): CannedResponse
    {
        abort_unless($response->scope === CannedResponse::SCOPE_GLOBAL || $response->user_id === $user->id, 403);

        $scope = $data['scope'] ?? $response->scope;

        $response->update([
            'user_id' => $scope === CannedResponse::SCOPE_PERSONAL ? $user->id : null,
            'scope' => $scope,
            'title' => $data['title'] ?? $response->title,
            'shortcut' => array_key_exists('shortcut', $data) ? $data['shortcut'] : $response->shortcut,
            'body' => array_key_exists('body', $data) ? $this->sanitizer->sanitize($data['body']) : $response->body,
            'variables' => $data['variables'] ?? $response->variables,
            'status' => $data['status'] ?? $response->status,
        ]);

        return $response->refresh();
    }

    public function render(CannedResponse $response, Ticket $ticket, User $actor): string
    {
        abort_unless($response->scope === CannedResponse::SCOPE_GLOBAL || $response->user_id === $actor->id, 403);

        $ticket->loadMissing(['company', 'requester', 'assignee']);

        $variables = [
            'ticket.id' => $ticket->public_id,
            'ticket.subject' => $ticket->subject,
            'company.name' => $ticket->company?->name ?? '',
            'requester.name' => $ticket->requester?->name ?? '',
            'assignee.name' => $ticket->assignee?->name ?? '',
            'agent.name' => $actor->name,
        ];

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function (array $matches) use ($variables): string {
            return $variables[$matches[1]] ?? $matches[0];
        }, $response->body) ?? $response->body;
    }
}
