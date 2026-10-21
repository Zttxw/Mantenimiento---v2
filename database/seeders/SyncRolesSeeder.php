<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Asignar coordinador_oti a superadmin
        $superadmin = User::where('username', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->syncRoles([RoleEnum::COORDINADOR_OTI->value]);
        }

        // 2. Eliminar roles obsoletos
        Role::whereIn('name', ['admin', 'empleado', 'responsable_oficina'])->delete();

        // 3. Crear usuario para el Jefe de OTI (Adrian Valer Bellota, DNI 40956781)
        $jefeEmployee = Employee::find(530);
        if ($jefeEmployee) {
            $jefeUser = User::updateOrCreate(
                ['username' => 'avaler'],
                [
                    'employee_id' => $jefeEmployee->id,
                    'email' => 'adrian.valer@munisanjeronimocusco.gob.pe',
                    'email_verified_at' => now(),
                    'password' => 'MdsjJefe2026!',
                    'is_active' => true,
                ]
            );
            $jefeUser->syncRoles([RoleEnum::JEFE_OTI->value]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
