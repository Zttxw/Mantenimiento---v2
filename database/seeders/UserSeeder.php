<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['dni' => '70123456', 'username' => 'cramirez', 'email' => 'carlos.ramirez@mdsj.local', 'password' => 'MdsjAdmin2026!', 'role' => RoleEnum::COORDINADOR_OTI->value],
            ['dni' => '70234567', 'username' => 'lquispe', 'email' => 'lucia.quispe@mdsj.local', 'password' => 'MdsjTecnico2026!', 'role' => RoleEnum::TECNICO->value],
            ['dni' => '70345678', 'username' => 'dmendoza', 'email' => 'diego.mendoza@mdsj.local', 'password' => 'MdsjTecnico2026!', 'role' => RoleEnum::TECNICO->value],
            ['dni' => '70456789', 'username' => 'rsalazar', 'email' => 'rosa.salazar@mdsj.local', 'password' => 'MdsjOficina2026!', 'role' => RoleEnum::JEFE_OTI->value],
            ['dni' => '70567890', 'username' => 'mvargas', 'email' => 'miguel.vargas@mdsj.local', 'password' => 'MdsjEmpleado2026!', 'role' => RoleEnum::AUXILIAR_TI->value],
        ];

        foreach ($users as $data) {
            $employee = Employee::where('dni', $data['dni'])->firstOrFail();

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'employee_id' => $employee->id,
                    'username' => $data['username'],
                    'email_verified_at' => now(),
                    'password' => $data['password'],
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
