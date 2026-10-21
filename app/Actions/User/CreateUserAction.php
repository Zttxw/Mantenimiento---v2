<?php

namespace App\Actions\User;

use App\DTOs\User\CreateUserDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserAction
{
    public function execute(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create($dto->toArray());
            if ($dto->role) {
                $user->assignRole($dto->role);
            }

            return $user;
        });
    }
}
