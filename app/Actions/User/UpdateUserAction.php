<?php

namespace App\Actions\User;

use App\DTOs\User\UpdateUserDTO;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    public function execute(User $user, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($user, $dto) {
            $data = $dto->toArray();

            if (blank($dto->password)) {
                unset($data['password']);
            }

            unset($data['role']);

            $user->update($data);
            $user->syncRoles([$dto->role]);

            $user->refresh();

            return $user;
        });
    }
}
