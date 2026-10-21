<?php

namespace App\Http\Requests\OrganizationalUnit;

use App\Enums\OrgUnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('organizational-unit.create');
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:organizational_units,id'],
            'type' => ['required', 'string', new Enum(OrgUnitType::class)],
            'code' => ['required', 'string', 'max:100', 'unique:organizational_units,code'],
            'name' => ['required', 'string', 'max:255'],
            'responsible_employee_id' => ['nullable', 'exists:employees,id'],
            'meta_json' => ['nullable', 'json'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
