<?php

namespace App\DTOs\Auth;

readonly class LoginDTO
{
    public function __construct(
        public string $username,
        public string $password,
        public bool $remember = false,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            username: $data['username'],
            password: $data['password'],
            remember: $data['remember'] ?? false
        );
    }
}
