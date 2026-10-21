<?php

namespace App\Http\Requests\Asset;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('asset.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'asset_type' => ['required', new Enum(AssetType::class)],
            'status' => ['required', new Enum(AssetStatus::class)],
            'condition' => ['required', new Enum(AssetCondition::class)],
            'internal_code' => ['nullable', 'string', 'max:50', 'unique:assets,internal_code'],
            'patrimonial_code' => ['nullable', 'string', 'max:50', 'unique:assets,patrimonial_code'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100', 'unique:assets,serial_number'],
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
