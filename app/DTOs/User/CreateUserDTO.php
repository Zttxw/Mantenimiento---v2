<?php

namespace App\DTOs\User;

readonly class CreateUserDTO
{
    public function __construct(
        public ?int $employee_id,
        public string $username,
        public string $email,
        public string $password,
        public ?string $role,
        public bool $is_active = true
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employee_id : $data['employee_id'] ?? null,
            username    : $data['username'],
            email       : $data['email'],
            password    : $data['password'],
            role        : $data['role'],
            is_active   : $data['is_active'] ?? true,

        );
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => $this->is_active,
        ];
    }
}
