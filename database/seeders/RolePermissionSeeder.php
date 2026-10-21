<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(PermissionEnum::cases())
            ->map(fn (PermissionEnum $permission) => Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]));

        $coordinador = Role::firstOrCreate([
            'name' => RoleEnum::COORDINADOR_OTI->value,
            'guard_name' => 'web',
        ]);

        $jefeOti = Role::firstOrCreate([
            'name' => RoleEnum::JEFE_OTI->value,
            'guard_name' => 'web',
        ]);

        $tecnico = Role::firstOrCreate([
            'name' => RoleEnum::TECNICO->value,
            'guard_name' => 'web',
        ]);

        $auxiliarTi = Role::firstOrCreate([
            'name' => RoleEnum::AUXILIAR_TI->value,
            'guard_name' => 'web',
        ]);

        // 1. Coordinador OTI (Superadmin): Todos los permisos
        $coordinador->syncPermissions($permissions);

        // 2. Jefe de OTI: Visibilidad general + Aprobación formal de campañas + Documentos
        $jefeOti->syncPermissions([
            PermissionEnum::USER_VIEW->value,
            PermissionEnum::EMPLOYEE_VIEW->value,
            PermissionEnum::ORG_UNIT_VIEW->value,
            PermissionEnum::ASSET_VIEW->value,
            PermissionEnum::ASSET_MOVEMENT_VIEW->value,
            PermissionEnum::CAMPAIGN_VIEW->value,
            PermissionEnum::CAMPAIGN_APPROVE->value,
            PermissionEnum::MAINTENANCE_CASE_VIEW->value,
            PermissionEnum::MAINTENANCE_TEMPLATE_VIEW->value,
            PermissionEnum::DOCUMENT_VIEW->value,
            PermissionEnum::DOCUMENT_CREATE->value,
            PermissionEnum::AGENT_VIEW->value,
            PermissionEnum::SETTING_VIEW->value,
        ]);

        // 3. Técnico Especialista: Cronograma campañas, atiende/cierra casos, saca reportes
        $tecnico->syncPermissions([
            PermissionEnum::ASSET_VIEW->value,
            PermissionEnum::ASSET_CREATE->value,
            PermissionEnum::ASSET_EDIT->value,
            PermissionEnum::ASSET_MOVEMENT_VIEW->value,
            PermissionEnum::ASSET_MOVEMENT_CREATE->value,
            PermissionEnum::EMPLOYEE_VIEW->value,
            PermissionEnum::ORG_UNIT_VIEW->value,
            PermissionEnum::CAMPAIGN_VIEW->value,
            PermissionEnum::CAMPAIGN_CREATE->value,
            PermissionEnum::CAMPAIGN_EDIT->value,
            PermissionEnum::MAINTENANCE_CASE_VIEW->value,
            PermissionEnum::MAINTENANCE_CASE_CREATE->value,
            PermissionEnum::MAINTENANCE_CASE_EDIT->value,
            PermissionEnum::MAINTENANCE_CASE_CLOSE->value,
            PermissionEnum::MAINTENANCE_TEMPLATE_VIEW->value,
            PermissionEnum::MAINTENANCE_TEMPLATE_CREATE->value,
            PermissionEnum::MAINTENANCE_TEMPLATE_EDIT->value,
            PermissionEnum::DOCUMENT_VIEW->value,
            PermissionEnum::DOCUMENT_CREATE->value,
            PermissionEnum::AGENT_VIEW->value,
        ]);

        // 4. Auxiliar de TI: Soporte asistencial en tickets asignados
        $auxiliarTi->syncPermissions([
            PermissionEnum::ASSET_VIEW->value,
            PermissionEnum::EMPLOYEE_VIEW->value,
            PermissionEnum::MAINTENANCE_CASE_VIEW->value,
            PermissionEnum::MAINTENANCE_CASE_EDIT->value,
            PermissionEnum::DOCUMENT_VIEW->value,
        ]);
    }
}
