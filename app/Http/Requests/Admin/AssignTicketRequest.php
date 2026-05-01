<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.assign') === true;
    }

    public function rules(): array
    {
        return [
            'assigned_to_user_id' => ['nullable', 'exists:users,public_id'],
        ];
    }
}
