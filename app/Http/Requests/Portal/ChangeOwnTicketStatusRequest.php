<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeOwnTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.close_own') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['resolved', 'closed'])],
        ];
    }
}
