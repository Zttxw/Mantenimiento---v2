<?php

namespace App\DTOs\Role;

readonly class RoleDTO
{
    public function __construct(
        public string $name,
        public array $permissions = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            permissions: $data['permissions'] ?? [],
        );
    }
}
