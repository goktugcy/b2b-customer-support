<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketWatcherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.add_watcher') === true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,public_id'],
        ];
    }
}
