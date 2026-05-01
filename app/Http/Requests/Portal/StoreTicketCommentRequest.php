<?php

namespace App\Http\Requests\Portal;

use App\Support\AttachmentValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.comment_public') === true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => AttachmentValidationRules::upload(required: false),
        ];
    }
}
