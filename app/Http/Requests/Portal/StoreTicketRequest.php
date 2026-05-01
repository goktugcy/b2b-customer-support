<?php

namespace App\Http\Requests\Portal;

use App\Enums\TicketPriority;
use App\Support\AttachmentValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.create') === true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'exists:support_projects,public_id'],
            'tracker_id' => ['required', 'exists:ticket_trackers,public_id'],
            'category_id' => ['nullable', 'exists:ticket_categories,public_id'],
            'description' => ['required', 'string', 'max:20000'],
            'priority' => ['required', Rule::in(array_map(fn (TicketPriority $priority) => $priority->value, TicketPriority::cases()))],
            'tag_names' => ['nullable', 'array', 'max:20'],
            'tag_names.*' => ['string', 'max:40'],
            'custom_fields' => ['nullable', 'array'],
            'target_department_ids' => ['nullable', 'array'],
            'target_department_ids.*' => ['string', 'exists:support_departments,public_id'],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['string', 'exists:users,public_id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => AttachmentValidationRules::upload(required: false),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $departmentIds = collect($this->input('target_department_ids', []))->filter();
            $userIds = collect($this->input('target_user_ids', []))->filter();

            if ($departmentIds->isEmpty() && $userIds->isEmpty()) {
                $validator->errors()->add('targets', 'Select at least one target department or provider user.');
            }
        });
    }
}
