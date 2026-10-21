<?php

namespace App\Http\Requests\AssetMovement;

use App\Enums\MovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAssetMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('asset-movement.create');
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'movement_type' => ['required', new Enum(MovementType::class)],
            'origin_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'destination_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'from_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'to_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'movement_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
