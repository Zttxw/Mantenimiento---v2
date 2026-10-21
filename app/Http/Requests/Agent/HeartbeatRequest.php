<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'agent_version' => ['required', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'sent_at_utc' => ['nullable', 'date'],
            'last_ip' => ['nullable', 'ip'],
            'last_snapshot_json' => ['nullable', 'array'],
            'health' => ['nullable', 'array'],
            'asset_code' => ['nullable', 'string', 'max:100'],
            'organizational_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'responsible_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'agent_version' => $this->input('agent_version')
                ?? $this->input('agentVersion'),
            'status' => $this->input('status'),
            'sent_at_utc' => $this->input('sent_at_utc')
                ?? $this->input('sentAtUtc'),
            'last_ip' => $this->input('last_ip')
                ?? $this->input('lastIp'),
            'last_snapshot_json' => $this->input('last_snapshot_json')
                ?? $this->input('lastSnapshotJson'),
            'health' => $this->input('health'),
            'asset_code' => $this->input('asset_code')
                ?? $this->input('assetCode'),
            'organizational_unit_id' => $this->input('organizational_unit_id')
                ?? $this->input('organizationalUnitId'),
            'responsible_employee_id' => $this->input('responsible_employee_id')
                ?? $this->input('responsibleEmployeeId'),
        ]);
    }
}
