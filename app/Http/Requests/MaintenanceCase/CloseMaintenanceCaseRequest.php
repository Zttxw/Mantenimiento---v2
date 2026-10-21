<?php

namespace App\Http\Requests\MaintenanceCase;

use Illuminate\Foundation\Http\FormRequest;

class CloseMaintenanceCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('maintenance-case.close');
    }

    public function rules(): array
    {
        return [
            'actions_taken' => ['required', 'string', 'max:2000'],
            'finished_at' => ['nullable', 'date'],
            'next_maintenance_date' => ['nullable', 'date'],
            'conformity_name' => ['nullable', 'string', 'max:200'],
            'conformity_date' => ['nullable', 'date'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
