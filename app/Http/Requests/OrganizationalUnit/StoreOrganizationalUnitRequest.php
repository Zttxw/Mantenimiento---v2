<?php

namespace App\Http\Requests\OrganizationalUnit;

use App\Enums\OrgUnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('org-unit.create') || auth()->user()->can('organizational-unit.create');
    }

    protected function prepareForValidation(): void
    {
        $name = trim($this->input('name', ''));

        if (empty($this->code) && !empty($name)) {
            $words = explode(' ', preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
            $initials = '';
            foreach ($words as $w) {
                $lw = strtolower($w);
                if (strlen($w) > 2 && !in_array($lw, ['de', 'la', 'los', 'las', 'del', 'y', 'en', 'para', 'con'])) {
                    $initials .= strtoupper(substr($w, 0, 1));
                }
            }
            $baseCode = $initials ?: strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 6));
            $candidate = $baseCode;
            $counter = 1;
            while (\App\Models\OrganizationalUnit::where('code', $candidate)->exists()) {
                $candidate = $baseCode . '-' . $counter++;
            }
            $this->merge(['code' => $candidate]);
        }

        if (empty($this->type) && !empty($name)) {
            $this->merge(['type' => OrgUnitType::fromName($name)->value]);
        }

        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
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
