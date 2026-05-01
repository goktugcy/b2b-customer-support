<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.change_status') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_map(fn (TicketStatus $status) => $status->value, TicketStatus::cases()))],
        ];
    }
}
