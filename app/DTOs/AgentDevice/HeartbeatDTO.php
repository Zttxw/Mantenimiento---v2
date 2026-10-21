<?php

namespace App\DTOs\AgentDevice;

readonly class HeartbeatDTO
{
    public function __construct(
        public string $agent_version,
        public ?string $status,
        public ?string $sent_at_utc,
        public ?string $last_ip,
        public ?array $last_snapshot_json,
        public ?array $health,
        public ?string $asset_code,
        public ?int $organizational_unit_id,
        public ?int $responsible_employee_id,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agent_version: $data['agent_version'],
            status: $data['status'] ?? null,
            sent_at_utc: $data['sent_at_utc'] ?? null,
            last_ip: $data['last_ip'] ?? null,
            last_snapshot_json: $data['last_snapshot_json'] ?? null,
            health: $data['health'] ?? null,
            asset_code: $data['asset_code'] ?? null,
            organizational_unit_id: $data['organizational_unit_id'] ?? null,
            responsible_employee_id: $data['responsible_employee_id'] ?? null,
        );
    }
}
