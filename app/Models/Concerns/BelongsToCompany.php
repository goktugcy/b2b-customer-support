<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id')) {
                return;
            }

            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $tenant = app(TenantContext::class);

            if ($tenant->isRestricted() && $tenant->companyId()) {
                $model->setAttribute('company_id', $tenant->companyId());
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, int|Company $company): Builder
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;

        return $query->where($query->qualifyColumn('company_id'), $companyId);
    }
}
