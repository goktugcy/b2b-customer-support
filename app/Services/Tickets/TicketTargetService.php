<?php

namespace App\Services\Tickets;

use App\Enums\CompanyType;
use App\Models\ApiClient;
use App\Models\SupportDepartment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketTargetService
{
    public function __construct(private readonly TicketEventRecorder $events) {}

    /**
     * @param  list<string>  $departmentPublicIds
     * @param  list<string>  $userPublicIds
     */
    public function sync(Ticket $ticket, array $departmentPublicIds, array $userPublicIds, User|ApiClient|null $actor = null, ?Request $request = null): void
    {
        $departments = $this->resolveDepartments($departmentPublicIds);
        $users = $this->resolveProviderUsers($userPublicIds);

        if ($departments->isEmpty() && $users->isEmpty()) {
            throw ValidationException::withMessages([
                'targets' => 'Select at least one target department or provider user.',
            ]);
        }

        DB::transaction(function () use ($ticket, $departments, $users, $actor, $request): void {
            $oldValues = [
                'department_ids' => $ticket->targetDepartments()->pluck('support_departments.public_id')->values()->all(),
                'user_ids' => $ticket->targetUsers()->pluck('users.public_id')->values()->all(),
            ];

            $pivot = ['added_by_user_id' => $actor instanceof User ? $actor->id : null];

            $ticket->targetDepartments()->syncWithPivotValues($departments->pluck('id')->all(), $pivot);
            $ticket->targetUsers()->syncWithPivotValues($users->pluck('id')->all(), $pivot);

            $newValues = [
                'department_ids' => $departments->pluck('public_id')->values()->all(),
                'user_ids' => $users->pluck('public_id')->values()->all(),
            ];

            if ($oldValues !== $newValues) {
                $this->events->record(
                    ticket: $ticket,
                    eventType: 'ticket.targets_updated',
                    actor: $actor,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    request: $request,
                );
            }
        });
    }

    /**
     * @param  list<string>  $departmentPublicIds
     * @return EloquentCollection<int, SupportDepartment>
     */
    public function resolveDepartments(array $departmentPublicIds): EloquentCollection
    {
        $ids = collect($departmentPublicIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return new EloquentCollection;
        }

        $departments = SupportDepartment::query()
            ->whereIn('public_id', $ids)
            ->whereHas('company', fn ($query) => $query->where('type', CompanyType::Provider->value))
            ->where('status', 'active')
            ->get();

        if ($departments->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'target_department_ids' => 'One or more target departments are invalid.',
            ]);
        }

        return $departments;
    }

    /**
     * @param  list<string>  $userPublicIds
     * @return EloquentCollection<int, User>
     */
    public function resolveProviderUsers(array $userPublicIds): EloquentCollection
    {
        $ids = collect($userPublicIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return new EloquentCollection;
        }

        $users = User::query()
            ->whereIn('public_id', $ids)
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->where('type', CompanyType::Provider->value))
            ->get();

        if ($users->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'target_user_ids' => 'One or more target users are invalid.',
            ]);
        }

        return $users;
    }

    public function notificationRecipients(Ticket $ticket): Collection
    {
        $ticket->loadMissing(['targetUsers', 'targetDepartments.users']);

        return $ticket->targetUsers
            ->merge($ticket->targetDepartments->flatMap->users)
            ->unique('id')
            ->values();
    }
}
