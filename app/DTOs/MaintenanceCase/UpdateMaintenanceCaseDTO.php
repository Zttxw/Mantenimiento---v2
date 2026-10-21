<?php

namespace App\DTOs\MaintenanceCase;

use App\Enums\MaintenanceCaseStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;

readonly class UpdateMaintenanceCaseDTO
{
    public function __construct(
        public MaintenanceType $maintenance_type,
        public MaintenancePriority $priority,
        public MaintenanceCaseStatus $status,
        public string $problem_description,
        public ?int $assigned_technician_id,
        public ?string $diagnosis,
        public ?string $actions_taken,
        public ?string $started_at,
        public ?string $finished_at,
        public ?string $next_maintenance_date,
        public ?string $conformity_name,
        public ?string $conformity_date,
        public ?float $total_cost,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            maintenance_type: MaintenanceType::from($data['maintenance_type']),
            priority: MaintenancePriority::from($data['priority']),
            status: MaintenanceCaseStatus::from($data['status']),
            problem_description: $data['problem_description'],
            assigned_technician_id: $data['assigned_technician_id'] ?? null,
            diagnosis: $data['diagnosis'] ?? null,
            actions_taken: $data['actions_taken'] ?? null,
            started_at: $data['started_at'] ?? null,
            finished_at: $data['finished_at'] ?? null,
            next_maintenance_date: $data['next_maintenance_date'] ?? null,
            conformity_name: $data['conformity_name'] ?? null,
            conformity_date: $data['conformity_date'] ?? null,
            total_cost: $data['total_cost'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'maintenance_type' => $this->maintenance_type->value,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'problem_description' => $this->problem_description,
            'assigned_technician_id' => $this->assigned_technician_id,
            'diagnosis' => $this->diagnosis,
            'actions_taken' => $this->actions_taken,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'next_maintenance_date' => $this->next_maintenance_date,
            'conformity_name' => $this->conformity_name,
            'conformity_date' => $this->conformity_date,
            'total_cost' => $this->total_cost,
            'notes' => $this->notes,
        ];
    }
}
