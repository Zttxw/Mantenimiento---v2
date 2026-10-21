<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => RoleEnum::COORDINADOR_OTI->value,
            'guard_name' => 'web',
        ]);

        $user = User::updateOrCreate(
            ['email' => 'superadmin@mdsj.local'],
            [
                'username' => 'superadmin',
                'email_verified_at' => now(),
                'password' => 'MdsjAdmin2026!',
                'is_active' => true,
            ]
        );

        $user->syncRoles([$role->name]);
    }
}
