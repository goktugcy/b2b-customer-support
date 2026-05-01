<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicId
{
    public function initializeHasPublicId(): void
    {
        $this->usesUniqueIds = true;
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function newUniqueId(): string
    {
        return (string) Str::ulid();
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
