<?php

namespace App\Http\Requests\OrganizationalUnit;

use App\Enums\OrgUnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('org-unit.edit');
    }

    public function rules(): array
    {
        $unit = $this->route('organizational_unit');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'type' => ['required', new Enum(OrgUnitType::class)],
            'code' => ['required', 'string', 'max:20', Rule::unique('organizational_units', 'code')->ignore($unit)],
            'name' => ['required', 'string', 'max:200'],
            'responsible_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'meta_json' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
