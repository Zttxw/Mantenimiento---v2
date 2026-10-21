<?php

namespace App\Http\Requests\MaintenanceCase;

use App\Enums\MaintenanceCaseStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMaintenanceCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('maintenance-case.edit');
    }

    public function rules(): array
    {
        return [
            'maintenance_type' => ['required', new Enum(MaintenanceType::class)],
            'priority' => ['required', new Enum(MaintenancePriority::class)],
            'status' => ['required', new Enum(MaintenanceCaseStatus::class)],
            'problem_description' => ['required', 'string', 'max:2000'],
            'assigned_technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'actions_taken' => ['nullable', 'string', 'max:2000'],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'next_maintenance_date' => ['nullable', 'date'],
            'conformity_name' => ['nullable', 'string', 'max:200'],
            'conformity_date' => ['nullable', 'date'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
