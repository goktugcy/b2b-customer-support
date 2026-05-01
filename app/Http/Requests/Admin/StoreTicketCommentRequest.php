<?php

namespace App\Http\Requests\Admin;

use App\Enums\TicketVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.comment_public') === true
            || $this->user()?->can('tickets.comment_internal') === true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
            'visibility' => ['required', Rule::in([TicketVisibility::Public->value, TicketVisibility::Internal->value])],
        ];
    }
}
