<?php

namespace App\Services\Tickets;

use App\Models\TicketSavedView;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TicketSavedViewService
{
    /**
     * @return Collection<int, TicketSavedView>
     */
    public function visibleTo(User $user, string $section): Collection
    {
        return TicketSavedView::query()
            ->visibleTo($user, $section)
            ->with('user:id,public_id,name')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function store(User $user, string $section, array $data): TicketSavedView
    {
        return DB::transaction(function () use ($user, $section, $data): TicketSavedView {
            if ((bool) ($data['is_default'] ?? false)) {
                $this->clearDefaults($user, $section);
            }

            return TicketSavedView::create([
                'company_id' => $data['is_shared'] ? $this->companyIdForSharedView($user) : $user->company_id,
                'user_id' => $user->id,
                'section' => $section,
                'name' => $data['name'],
                'filters' => $data['filters'] ?? [],
                'columns' => $data['columns'] ?? null,
                'sort' => $data['sort'] ?? null,
                'is_shared' => (bool) ($data['is_shared'] ?? false),
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);
        });
    }

    public function update(TicketSavedView $view, User $user, array $data): TicketSavedView
    {
        $this->assertOwner($view, $user);

        return DB::transaction(function () use ($view, $user, $data): TicketSavedView {
            if ((bool) ($data['is_default'] ?? false)) {
                $this->clearDefaults($user, $view->section);
            }

            $view->update([
                'company_id' => ($data['is_shared'] ?? $view->is_shared)
                    ? $this->companyIdForSharedView($user)
                    : $user->company_id,
                'name' => $data['name'] ?? $view->name,
                'filters' => $data['filters'] ?? $view->filters,
                'columns' => array_key_exists('columns', $data) ? $data['columns'] : $view->columns,
                'sort' => array_key_exists('sort', $data) ? $data['sort'] : $view->sort,
                'is_shared' => (bool) ($data['is_shared'] ?? $view->is_shared),
                'is_default' => (bool) ($data['is_default'] ?? $view->is_default),
            ]);

            return $view->refresh();
        });
    }

    public function delete(TicketSavedView $view, User $user): void
    {
        $this->assertOwner($view, $user);

        $view->delete();
    }

    public function payload(TicketSavedView $view): array
    {
        return [
            'id' => $view->public_id,
            'name' => $view->name,
            'filters' => $view->filters ?? [],
            'columns' => $view->columns,
            'sort' => $view->sort,
            'is_shared' => $view->is_shared,
            'is_default' => $view->is_default,
            'owner' => $view->user?->name,
        ];
    }

    private function clearDefaults(User $user, string $section): void
    {
        TicketSavedView::query()
            ->where('user_id', $user->id)
            ->where('section', $section)
            ->update(['is_default' => false]);
    }

    private function companyIdForSharedView(User $user): ?int
    {
        return $user->isProviderUser() ? null : $user->company_id;
    }

    private function assertOwner(TicketSavedView $view, User $user): void
    {
        abort_unless($view->user_id === $user->id, 403);
    }
}
