<?php

namespace App\Http\Requests\MaintenanceCampaign;

use App\Enums\CampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMaintenanceCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('campaign.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'objective' => ['nullable', 'string', 'max:1000'],
            'scope_json' => ['nullable', 'array'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', new Enum(CampaignStatus::class)],
            'coordinator_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'summary' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
