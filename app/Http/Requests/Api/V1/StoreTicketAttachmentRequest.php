<?php

namespace App\Http\Requests\Api\V1;

use App\Support\AttachmentValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('attachments:create') === true;
    }

    public function rules(): array
    {
        return [
            'file' => AttachmentValidationRules::upload(),
        ];
    }
}
