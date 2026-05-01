<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $customFieldValues = $this->customFieldValues
            ->keyBy('ticket_custom_field_id')
            ->map(fn ($value) => $value->value['value'] ?? null);
        $customFields = $this->tracker?->customFields ?? collect();

        return [
            'id' => $this->public_id,
            'company_id' => $this->company?->public_id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'source' => $this->source->value,
            'project' => $this->supportProject ? [
                'id' => $this->supportProject->public_id,
                'name' => $this->supportProject->name,
            ] : null,
            'tracker' => $this->tracker ? [
                'id' => $this->tracker->public_id,
                'name' => $this->tracker->name,
                'color' => $this->tracker->color,
            ] : null,
            'category' => $this->category ? [
                'id' => $this->category->public_id,
                'name' => $this->category->name,
            ] : null,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag): array => [
                'id' => $tag->public_id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values()),
            'custom_fields' => $customFields->map(fn ($field): array => [
                'id' => $field->public_id,
                'name' => $field->name,
                'slug' => $field->slug,
                'type' => $field->type,
                'required' => $field->is_required,
                'options' => $field->options->map(fn ($option): array => [
                    'value' => $option->value,
                    'label' => $option->label,
                ])->values(),
                'value' => $customFieldValues[$field->id] ?? null,
            ])->values(),
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
