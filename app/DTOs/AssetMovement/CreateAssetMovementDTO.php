<?php

namespace App\DTOs\AssetMovement;

use App\Enums\MovementType;

readonly class CreateAssetMovementDTO
{
    public function __construct(
        public int $asset_id,
        public MovementType $movement_type,
        public ?int $origin_unit_id,
        public ?int $destination_unit_id,
        public ?int $from_employee_id,
        public ?int $to_employee_id,
        public string $movement_date,
        public string $reason,
        public ?string $document_number,
        public ?string $notes,
        public ?int $created_by,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            asset_id: $data['asset_id'],
            movement_type: MovementType::from($data['movement_type']),
            origin_unit_id: $data['origin_unit_id'] ?? null,
            destination_unit_id: $data['destination_unit_id'] ?? null,
            from_employee_id: $data['from_employee_id'] ?? null,
            to_employee_id: $data['to_employee_id'] ?? null,
            movement_date: $data['movement_date'],
            reason: $data['reason'],
            document_number: $data['document_number'] ?? null,
            notes: $data['notes'] ?? null,
            created_by: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'asset_id' => $this->asset_id,
            'movement_type' => $this->movement_type->value,
            'origin_unit_id' => $this->origin_unit_id,
            'destination_unit_id' => $this->destination_unit_id,
            'from_employee_id' => $this->from_employee_id,
            'to_employee_id' => $this->to_employee_id,
            'movement_date' => $this->movement_date,
            'reason' => $this->reason,
            'document_number' => $this->document_number,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
        ];
    }
}
