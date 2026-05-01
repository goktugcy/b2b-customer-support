<?php

namespace App\Services\Tenancy;

use App\Models\Company;

class TenantContext
{
    public function __construct(
        protected ?Company $company = null,
        protected bool $restricted = false,
    ) {}

    public function set(?Company $company, bool $restricted): void
    {
        $this->company = $company;
        $this->restricted = $restricted;
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function companyId(): ?int
    {
        return $this->company?->getKey();
    }

    public function isRestricted(): bool
    {
        return $this->restricted;
    }

    public function clear(): void
    {
        $this->company = null;
        $this->restricted = false;
    }
}
