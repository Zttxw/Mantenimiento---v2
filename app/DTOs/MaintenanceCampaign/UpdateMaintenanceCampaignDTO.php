<?php

namespace App\DTOs\MaintenanceCampaign;

use App\Enums\CampaignStatus;

readonly class UpdateMaintenanceCampaignDTO
{
    public function __construct(
        public string $name,
        public ?string $objective,
        public ?array $scope_json,
        public string $start_date,
        public string $end_date,
        public CampaignStatus $status,
        public ?int $coordinator_employee_id,
        public ?string $summary,
        public ?array $metrics_json,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            objective: $data['objective'] ?? null,
            scope_json: $data['scope_json'] ?? null,
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            status: CampaignStatus::from($data['status']),
            coordinator_employee_id: $data['coordinator_employee_id'] ?? null,
            summary: $data['summary'] ?? null,
            metrics_json: $data['metrics_json'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'objective' => $this->objective,
            'scope_json' => $this->scope_json,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status->value,
            'coordinator_employee_id' => $this->coordinator_employee_id,
            'summary' => $this->summary,
            'metrics_json' => $this->metrics_json,
        ];
    }
}
