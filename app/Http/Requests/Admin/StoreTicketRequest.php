<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.create') === true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,public_id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'priority' => ['required', Rule::in(array_map(fn (TicketPriority $priority) => $priority->value, TicketPriority::cases()))],
            'assigned_to_user_id' => ['nullable', 'exists:users,public_id'],
        ];
    }
}
