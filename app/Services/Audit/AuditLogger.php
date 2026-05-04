<?php

namespace App\Services\Audit;

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(
        string $action,
        ?Model $auditable = null,
        User|ApiClient|null $actor = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
        ?Request $request = null,
    ): AuditLog {
        $companyId = $auditable instanceof Company
            ? $auditable->id
            : ($auditable?->getAttribute('company_id') ?? $actor?->getAttribute('company_id'));

        return AuditLog::create([
            'company_id' => $companyId,
            'actor_user_id' => $actor instanceof User ? $actor->id : null,
            'api_client_id' => $actor instanceof ApiClient ? $actor->id : null,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
