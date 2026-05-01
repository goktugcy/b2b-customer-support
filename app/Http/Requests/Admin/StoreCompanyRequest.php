<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompanyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('companies.manage') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:companies,slug'],
            'type' => ['required', Rule::in([CompanyType::Client->value, CompanyType::Provider->value])],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
