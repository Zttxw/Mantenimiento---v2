<?php

namespace App\DTOs\MaintenanceItem;

use App\Enums\MaintenanceItemType;

readonly class CreateMaintenanceItemDTO
{
    public function __construct(
        public int $maintenance_case_id,
        public MaintenanceItemType $item_type,
        public string $name,
        public ?string $description,
        public int $quantity,
        public float $unit_cost,
        public ?array $data_json,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            maintenance_case_id: $data['maintenance_case_id'],
            item_type: MaintenanceItemType::from($data['item_type']),
            name: $data['name'],
            description: $data['description'] ?? null,
            quantity: $data['quantity'],
            unit_cost: $data['unit_cost'],
            data_json: $data['data_json'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'maintenance_case_id' => $this->maintenance_case_id,
            'item_type' => $this->item_type->value,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->quantity * $this->unit_cost,
            'data_json' => $this->data_json,
        ];
    }
}
