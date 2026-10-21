<?php

namespace App\DTOs\Asset;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssetType;

readonly class UpdateAssetDTO
{
    public function __construct(
        public string $name,
        public AssetType $asset_type,
        public AssetStatus $status,
        public AssetCondition $condition,
        public ?string $internal_code,
        public ?string $patrimonial_code,
        public ?string $brand,
        public ?string $model,
        public ?string $serial_number,
        public ?int $organizational_unit_id,
        public ?int $responsible_employee_id,
        public ?string $purchase_date,
        public ?float $reference_value,
        public ?array $specs_json,
        public ?array $extra_json,
        public ?string $notes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            asset_type: AssetType::from($data['asset_type']),
            status: AssetStatus::from($data['status']),
            condition: AssetCondition::from($data['condition']),
            internal_code: $data['internal_code'] ?? null,
            patrimonial_code: $data['patrimonial_code'] ?? null,
            brand: $data['brand'] ?? null,
            model: $data['model'] ?? null,
            serial_number: $data['serial_number'] ?? null,
            organizational_unit_id: $data['organizational_unit_id'] ?? null,
            responsible_employee_id: $data['responsible_employee_id'] ?? null,
            purchase_date: $data['purchase_date'] ?? null,
            reference_value: $data['reference_value'] ?? null,
            specs_json: isset($data['specs_json']) ? (is_array($data['specs_json']) ? $data['specs_json'] : json_decode($data['specs_json'], true)) : null,
            extra_json: isset($data['extra_json']) ? (is_array($data['extra_json']) ? $data['extra_json'] : json_decode($data['extra_json'], true)) : null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'asset_type' => $this->asset_type->value,
            'status' => $this->status->value,
            'condition' => $this->condition->value,
            'internal_code' => $this->internal_code,
            'patrimonial_code' => $this->patrimonial_code,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'organizational_unit_id' => $this->organizational_unit_id,
            'responsible_employee_id' => $this->responsible_employee_id,
            'purchase_date' => $this->purchase_date,
            'reference_value' => $this->reference_value,
            'specs_json' => $this->specs_json,
            'extra_json' => $this->extra_json,
            'notes' => $this->notes,
        ];
    }
}
