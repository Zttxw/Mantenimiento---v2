<?php

namespace App\DTOs\Employee;

readonly class CreateEmployeeDTO
{
    public function __construct(
        public string $dni,
        public string $name,
        public string $last_name,
        public string $full_name,
        public ?string $email,
        public ?string $phone,
        public ?string $position,
        public ?int $organizational_unit_id,
        public bool $is_technician = false,
        public ?string $specialty = null,
        public ?string $level = null,
        public bool $is_active = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            dni: $data['dni'],
            name: $data['name'],
            last_name: $data['last_name'],
            full_name: $data['full_name'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            position: $data['position'] ?? null,
            organizational_unit_id: $data['organizational_unit_id'] ?? null,
            is_technician: $data['is_technician'] ?? false,
            specialty: $data['specialty'] ?? null,
            level: $data['level'] ?? null,
            is_active: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'dni' => $this->dni,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'organizational_unit_id' => $this->organizational_unit_id,
            'is_technician' => $this->is_technician,
            'specialty' => $this->specialty,
            'level' => $this->level,
            'is_active' => $this->is_active,
        ];
    }
}
