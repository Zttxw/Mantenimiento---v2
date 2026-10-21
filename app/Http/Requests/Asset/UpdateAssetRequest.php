<?php

namespace App\Http\Requests\Asset;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('asset.edit');
    }

    public function rules(): array
    {
        $asset = $this->route('asset');

        return [
            'name' => ['required', 'string', 'max:200'],
            'asset_type' => ['required', new Enum(AssetType::class)],
            'status' => ['required', new Enum(AssetStatus::class)],
            'condition' => ['required', new Enum(AssetCondition::class)],
            'internal_code' => ['nullable', 'string', 'max:50', Rule::unique('assets', 'internal_code')->ignore($asset)->whereNull('deleted_at')],
            'patrimonial_code' => ['nullable', 'string', 'max:50', Rule::unique('assets', 'patrimonial_code')->ignore($asset)->whereNull('deleted_at')],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('assets', 'serial_number')->ignore($asset)->whereNull('deleted_at')],
            'organizational_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'responsible_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'purchase_date' => ['nullable', 'date'],
            'reference_value' => ['nullable', 'numeric', 'min:0'],
            'specs_json' => ['nullable', 'array'],
            'extra_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
