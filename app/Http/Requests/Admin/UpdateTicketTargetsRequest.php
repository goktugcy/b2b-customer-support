<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTicketTargetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.manage_targets') === true;
    }

    public function rules(): array
    {
        return [
            'target_department_ids' => ['nullable', 'array'],
            'target_department_ids.*' => ['string', 'exists:support_departments,public_id'],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['string', 'exists:users,public_id'],
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
