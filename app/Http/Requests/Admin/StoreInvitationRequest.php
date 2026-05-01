<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.invite') === true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,public_id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role_name' => ['required', Rule::in(array_map(fn (RoleName $role) => $role->value, RoleName::cases()))],
        ];
    }
}
