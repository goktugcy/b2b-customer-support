<?php

use App\Models\Company;
use App\Models\Ticket;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{publicId}', function ($user, string $publicId): bool {
    return $user->public_id === $publicId;
});

Broadcast::channel('tickets.{publicId}', function ($user, string $publicId): bool {
    return Ticket::query()
        ->where('public_id', $publicId)
        ->visibleTo($user)
        ->exists();
});

Broadcast::channel('companies.{publicId}', function ($user, string $publicId): bool {
    $company = Company::query()->where('public_id', $publicId)->first();

    if (! $company) {
        return false;
    }

    return $user->isProviderUser() || $user->company_id === $company->id;
});
