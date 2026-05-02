<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSavedView extends Model
{
    use HasFactory, HasPublicId;

    public const SECTION_ADMIN = 'admin';

    public const SECTION_PORTAL = 'portal';

    protected $fillable = [
        'public_id',
        'company_id',
        'user_id',
        'section',
        'name',
        'filters',
        'columns',
        'sort',
        'is_shared',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'columns' => 'array',
            'sort' => 'array',
            'is_shared' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user, string $section): Builder
    {
        return $query
            ->where('section', $section)
            ->where(function (Builder $scope) use ($user): void {
                $scope->where('user_id', $user->id)
                    ->orWhere(function (Builder $shared) use ($user): void {
                        $shared->where('is_shared', true)
                            ->where(function (Builder $company) use ($user): void {
                                $company->whereNull('company_id')
                                    ->orWhere('company_id', $user->company_id);
                            });
                    });
            });
    }
}
