<?php

namespace App\Models;

use App\Enums\SupportDepartmentStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SupportDepartment extends Model
{
    use BelongsToCompany, HasFactory, HasPublicId;

    protected $fillable = [
        'public_id',
        'company_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportDepartmentStatus::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'support_department_user')
            ->withTimestamps();
    }

    public function targetedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_target_departments')
            ->withPivot('added_by_user_id')
            ->withTimestamps();
    }
}
