<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;

class RegisterAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autenticación se controla via API token en el middleware
    }

    public function rules(): array
    {
        return [
            'asset_internal_code' => ['nullable', 'string'],
            'hostname' => ['required', 'string', 'max:200'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'device_model' => ['nullable', 'string', 'max:100'],
            'operating_system' => ['nullable', 'string', 'max:150'],
            'agent_version' => ['required', 'string', 'max:20'],
            'last_ip' => ['nullable', 'ip'],
            'last_snapshot_json' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asset_internal_code' => $this->input('asset_internal_code')
                ?? $this->input('assetCode')
                ?? $this->input('asset_code'),
            'hostname' => $this->input('hostname')
                ?? $this->input('deviceHostname')
                ?? $this->input('device_hostname'),
            'serial_number' => $this->input('serial_number')
                ?? $this->input('serialNumber')
                ?? $this->input('serial_number')
                ?? 'unknown',
            'device_model' => $this->input('device_model')
                ?? $this->input('deviceModel'),
            'operating_system' => $this->input('operating_system')
                ?? $this->input('operatingSystem')
                ?? $this->input('platform'),
            'agent_version' => $this->input('agent_version')
                ?? $this->input('agentVersion'),
            'last_ip' => $this->input('last_ip')
                ?? $this->input('lastIp'),
            'last_snapshot_json' => $this->input('last_snapshot_json')
                ?? $this->input('lastSnapshotJson'),
        ]);
    }
}
