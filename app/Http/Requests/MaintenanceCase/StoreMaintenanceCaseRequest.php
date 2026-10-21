<?php

namespace App\Http\Requests\MaintenanceCase;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMaintenanceCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('maintenance-case.create');
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'maintenance_type' => ['required', new Enum(MaintenanceType::class)],
            'priority' => ['required', new Enum(MaintenancePriority::class)],
            'problem_description' => ['required', 'string', 'max:2000'],
            'campaign_id' => ['nullable', 'integer', 'exists:maintenance_campaigns,id'],
            'reported_by_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'assigned_technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
