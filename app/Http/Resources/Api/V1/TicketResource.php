<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'company_id' => $this->company?->public_id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'source' => $this->source->value,
            'assigned_to' => $this->assignee ? [
                'id' => $this->assignee->public_id,
                'name' => $this->assignee->name,
            ] : null,
            'targets' => [
                'departments' => $this->whenLoaded('targetDepartments', fn () => $this->targetDepartments->map(fn ($department): array => [
                    'id' => $department->public_id,
                    'name' => $department->name,
                ])->values()),
                'users' => $this->whenLoaded('targetUsers', fn () => $this->targetUsers->map(fn ($user): array => [
                    'id' => $user->public_id,
                    'name' => $user->name,
                ])->values()),
            ],
            'comments' => TicketCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
