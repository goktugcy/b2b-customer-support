<?php

namespace App\Services\Search;

use App\Models\Company;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GlobalSearchService
{
    public function admin(User $user, string $query): array
    {
        $term = trim($query);

        if ($term === '') {
            return [];
        }

        return collect()
            ->merge($this->tickets($user, $term, 'admin'))
            ->merge($this->comments($user, $term, 'admin'))
            ->merge($this->companies($term))
            ->merge($this->users($term))
            ->merge($this->articles($term, includeInternal: $user->can('knowledge_base.view_internal'), section: 'admin'))
            ->take(12)
            ->values()
            ->all();
    }

    public function portal(User $user, string $query): array
    {
        $term = trim($query);

        if ($term === '') {
            return [];
        }

        return collect()
            ->merge($this->tickets($user, $term, 'portal'))
            ->merge($this->comments($user, $term, 'portal'))
            ->merge($this->articles($term, includeInternal: false, section: 'portal'))
            ->take(12)
            ->values()
            ->all();
    }

    private function tickets(User $user, string $term, string $section): Collection
    {
        $number = (int) ltrim($term, '#');

        return Ticket::query()
            ->visibleTo($user)
            ->with('company')
            ->where(function (Builder $query) use ($term, $number): void {
                $query->where('subject', 'like', '%'.$term.'%')
                    ->orWhere('public_id', $term);

                if ($number > 0) {
                    $query->orWhere('ticket_number', $number);
                }
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'type' => 'ticket',
                'title' => $ticket->displayId().' · '.$ticket->subject,
                'subtitle' => $ticket->company?->name.' · '.$ticket->status->value,
                'url' => $section === 'admin'
                    ? route('admin.tickets.show', $ticket->adminRouteParameters(), absolute: false)
                    : route('portal.tickets.show', $ticket->portalRouteParameters(), absolute: false),
            ]);
    }

    private function comments(User $user, string $term, string $section): Collection
    {
        return TicketComment::query()
            ->with('ticket.company')
            ->whereHas('ticket', fn (Builder $query) => $query->visibleTo($user))
            ->where('body', 'like', '%'.$term.'%')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (TicketComment $comment): array => [
                'type' => 'comment',
                'title' => ($comment->ticket?->displayId() ?? 'Ticket').' comment',
                'subtitle' => Str::limit(strip_tags($comment->body), 90),
                'url' => $comment->ticket && $section === 'admin'
                    ? route('admin.tickets.show', $comment->ticket->adminRouteParameters(), absolute: false)
                    : ($comment->ticket ? route('portal.tickets.show', $comment->ticket->portalRouteParameters(), absolute: false) : '#'),
            ]);
    }

    private function companies(string $term): Collection
    {
        return Company::query()
            ->clients()
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('slug', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit(3)
            ->get()
            ->map(fn (Company $company): array => [
                'type' => 'company',
                'title' => $company->name,
                'subtitle' => $company->slug,
                'url' => route('admin.companies.show', $company, absolute: false),
            ]);
    }

    private function users(string $term): Collection
    {
        return User::query()
            ->with('company')
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->limit(3)
            ->get()
            ->map(fn (User $user): array => [
                'type' => 'user',
                'title' => $user->name,
                'subtitle' => $user->email.' · '.$user->company?->name,
                'url' => route('admin.users.index', ['search' => $user->email], absolute: false),
            ]);
    }

    private function articles(string $term, bool $includeInternal, string $section): Collection
    {
        return KnowledgeBaseArticle::query()
            ->with('category')
            ->published()
            ->when(! $includeInternal, fn (Builder $query) => $query->public())
            ->where(fn (Builder $query) => $query
                ->where('title', 'like', '%'.$term.'%')
                ->orWhere('excerpt', 'like', '%'.$term.'%')
                ->orWhere('body', 'like', '%'.$term.'%'))
            ->latest('published_at')
            ->limit(4)
            ->get()
            ->map(fn (KnowledgeBaseArticle $article): array => [
                'type' => 'knowledge',
                'title' => $article->title,
                'subtitle' => ($article->category?->name ?? 'Knowledge base').' · '.$article->visibility,
                'url' => $section === 'portal'
                    ? route('portal.knowledge-base.show', $article->slug, absolute: false)
                    : route('admin.knowledge-base.index', ['search' => $article->title], absolute: false),
            ]);
    }
}
