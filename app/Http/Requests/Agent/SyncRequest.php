<?php

namespace App\Http\Requests\Agent;

use App\Enums\AgentSyncType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sync_type' => ['required', new Enum(AgentSyncType::class)],
            'payload_json' => ['nullable', 'array'],
            'detected_changes_json' => ['nullable', 'array'],
        ];
    }
}
