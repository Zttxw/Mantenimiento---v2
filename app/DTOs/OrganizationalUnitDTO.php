<?php

namespace App\DTOs;

use App\Enums\OrgUnitType;

class OrganizationalUnitDTO
{
    public function __construct(
        public readonly ?int $parent_id,
        public readonly OrgUnitType $type,
        public readonly string $code,
        public readonly string $name,
        public readonly ?int $responsible_employee_id,
        public readonly ?array $meta_json,
        public readonly bool $is_active,
        public readonly ?int $sort_order
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            parent_id: $data['parent_id'] ?? null,
            type: OrgUnitType::from($data['type']),
            code: $data['code'],
            name: $data['name'],
            responsible_employee_id: $data['responsible_employee_id'] ?? null,
            meta_json: $data['meta_json'] ?? null,
            is_active: $data['is_active'] ?? true,
            sort_order: $data['sort_order'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'parent_id' => $this->parent_id,
            'type' => $this->type->value,
            'code' => $this->code,
            'name' => $this->name,
            'responsible_employee_id' => $this->responsible_employee_id,
            'meta_json' => $this->meta_json,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
