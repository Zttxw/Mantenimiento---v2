<?php

namespace App\Http\Requests\MaintenanceItem;

use App\Enums\MaintenanceItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMaintenanceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('maintenance-case.edit');
    }

    public function rules(): array
    {
        return [
            'item_type' => ['required', new Enum(MaintenanceItemType::class)],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'data_json' => ['nullable', 'array'],
        ];
    }
}
