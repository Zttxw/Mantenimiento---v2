<?php

namespace App\DTOs\AgentDevice;

readonly class RegisterAgentDTO
{
    public function __construct(
        public string $hostname,
        public string $serial_number,
        public ?string $device_model,
        public ?string $operating_system,
        public string $agent_version,
        public ?string $last_ip,
        public ?array $last_snapshot_json,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            hostname: $data['hostname'],
            serial_number: $data['serial_number'],
            device_model: $data['device_model'] ?? null,
            operating_system: $data['operating_system'] ?? null,
            agent_version: $data['agent_version'],
            last_ip: $data['last_ip'] ?? null,
            last_snapshot_json: $data['last_snapshot_json'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'hostname' => $this->hostname,
            'serial_number' => $this->serial_number,
            'device_model' => $this->device_model,
            'operating_system' => $this->operating_system,
            'agent_version' => $this->agent_version,
            'last_ip' => $this->last_ip,
            'last_snapshot_json' => $this->last_snapshot_json,
        ];
    }
}
