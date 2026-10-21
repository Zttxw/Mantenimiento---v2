<?php

namespace App\DTOs\AgentSync;

use App\Enums\AgentSyncStatus;
use App\Enums\AgentSyncType;

readonly class CreateAgentSyncDTO
{
    public function __construct(
        public int $agent_device_id,
        public AgentSyncType $sync_type,
        public ?array $payload_json,
        public ?array $detected_changes_json,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agent_device_id: $data['agent_device_id'],
            sync_type: AgentSyncType::from($data['sync_type']),
            payload_json: $data['payload_json'] ?? null,
            detected_changes_json: $data['detected_changes_json'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'agent_device_id' => $this->agent_device_id,
            'sync_type' => $this->sync_type->value,
            'payload_json' => $this->payload_json,
            'detected_changes_json' => $this->detected_changes_json,
            'status' => AgentSyncStatus::RECIBIDO->value,
            'synced_at' => now(),
        ];
    }
}
