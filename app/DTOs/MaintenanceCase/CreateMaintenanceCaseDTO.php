<?php

namespace App\DTOs\MaintenanceCase;

use App\Enums\MaintenanceCaseStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;

readonly class CreateMaintenanceCaseDTO
{
    public function __construct(
        public int $asset_id,
        public MaintenanceType $maintenance_type,
        public MaintenancePriority $priority,
        public MaintenanceCaseStatus $status,
        public string $problem_description,
        public ?int $campaign_id,
        public ?int $reported_by_employee_id,
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
        public ?int $created_by,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            asset_id: $data['asset_id'],
            maintenance_type: MaintenanceType::from($data['maintenance_type']),
            priority: MaintenancePriority::from($data['priority']),
            status: MaintenanceCaseStatus::from($data['status'] ?? MaintenanceCaseStatus::PENDIENTE->value),
            problem_description: $data['problem_description'],
            campaign_id: $data['campaign_id'] ?? null,
            reported_by_employee_id: $data['reported_by_employee_id'] ?? null,
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
            created_by: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'asset_id' => $this->asset_id,
            'maintenance_type' => $this->maintenance_type->value,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'problem_description' => $this->problem_description,
            'campaign_id' => $this->campaign_id,
            'reported_by_employee_id' => $this->reported_by_employee_id,
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
            'created_by' => $this->created_by,
        ];
    }
}
