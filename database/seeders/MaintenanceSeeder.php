<?php

namespace Database\Seeders;

use App\Enums\CampaignAssetStatus;
use App\Enums\CampaignStatus;
use App\Enums\MaintenanceCaseStatus;
use App\Enums\MaintenanceItemType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\CampaignAsset;
use App\Models\Employee;
use App\Models\MaintenanceCampaign;
use App\Models\MaintenanceCase;
use App\Models\MaintenanceItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $assets = Asset::query()->get()->keyBy('internal_code');
        $employees = Employee::query()->get()->keyBy('dni');
        $createdBy = User::where('email', 'superadmin@mdsj.local')->value('id')
            ?? User::where('email', 'carlos.ramirez@mdsj.local')->value('id');

        $campaign = MaintenanceCampaign::updateOrCreate(
            ['code' => 'CMP-2026-001'],
            [
                'name' => 'Campana de mantenimiento preventivo 2026 - Equipos criticos',
                'objective' => 'Reducir incidencias en equipos de uso intensivo y de red.',
                'scope_json' => ['areas' => ['OTI', 'Tesoreria', 'Administracion'], 'frequency' => 'trimestral'],
                'start_date' => '2026-04-01',
                'end_date' => '2026-05-15',
                'status' => CampaignStatus::EN_CURSO,
                'coordinator_employee_id' => $employees['70123456']->id,
                'summary' => 'Campana en ejecucion con prioridad sobre estaciones de trabajo y red.',
                'metrics_json' => ['planned_assets' => 4, 'completed_assets' => 1],
                'created_by' => $createdBy,
            ]
        );

        $cases = [
            [
                'code' => 'MC-2026-001',
                'asset_id' => $assets['EQ-ALM-006']->id,
                'campaign_id' => null,
                'reported_by_employee_id' => $employees['70789012']->id,
                'assigned_technician_id' => $employees['70234567']->id,
                'maintenance_type' => MaintenanceType::CORRECTIVO,
                'priority' => MaintenancePriority::ALTA,
                'status' => MaintenanceCaseStatus::EN_PROGRESO,
                'problem_description' => 'La UPS presenta alarma constante y autonomia reducida.',
                'diagnosis' => 'Banco de baterias con desgaste avanzado.',
                'actions_taken' => 'Se aislo el equipo y se solicito reemplazo de baterias.',
                'started_at' => now()->subDays(2),
                'finished_at' => null,
                'next_maintenance_date' => now()->addMonths(3)->toDateString(),
                'total_cost' => 850.00,
                'notes' => 'Pendiente confirmacion de proveedor.',
                'created_by' => $createdBy,
            ],
            [
                'code' => 'MC-2026-002',
                'asset_id' => $assets['EQ-TES-002']->id,
                'campaign_id' => null,
                'reported_by_employee_id' => $employees['70456789']->id,
                'assigned_technician_id' => $employees['70345678']->id,
                'maintenance_type' => MaintenanceType::CORRECTIVO,
                'priority' => MaintenancePriority::MEDIA,
                'status' => MaintenanceCaseStatus::COMPLETADO,
                'problem_description' => 'Solicitud de asistencia: Reinstalación de certificado digital y limpieza de equipo.',
                'diagnosis' => 'Certificado vencido y acumulación de polvo.',
                'actions_taken' => 'Instalación de nuevo certificado digital, limpieza física y verificación de operatividad.',
                'started_at' => now()->subDays(10),
                'finished_at' => now()->subDays(9),
                'next_maintenance_date' => now()->addMonths(6)->toDateString(),
                'conformity_name' => 'Rosa Salazar Huaman',
                'conformity_date' => now()->subDays(9),
                'total_cost' => 120.00,
                'notes' => 'Conformidad firmada en oficina.',
                'created_by' => $createdBy,
            ],
            [
                'code' => 'MC-2026-003',
                'asset_id' => $assets['EQ-ADM-003']->id,
                'campaign_id' => null,
                'reported_by_employee_id' => $employees['70567890']->id,
                'assigned_technician_id' => $employees['70234567']->id,
                'maintenance_type' => MaintenanceType::CORRECTIVO,
                'priority' => MaintenancePriority::MEDIA,
                'status' => MaintenanceCaseStatus::PENDIENTE,
                'problem_description' => 'La impresora presenta lineas en las copias.',
                'diagnosis' => null,
                'actions_taken' => null,
                'started_at' => null,
                'finished_at' => null,
                'next_maintenance_date' => null,
                'total_cost' => 0,
                'notes' => 'Pendiente programacion.',
                'created_by' => $createdBy,
            ],
        ];

        $createdCases = [];

        foreach ($cases as $caseData) {
            $createdCases[$caseData['code']] = MaintenanceCase::updateOrCreate(
                ['code' => $caseData['code']],
                $caseData
            );
        }

        $items = [
            ['maintenance_case_id' => $createdCases['MC-2026-001']->id, 'item_type' => MaintenanceItemType::REPUESTO, 'name' => 'Bateria sellada 12V', 'description' => 'Reemplazo de modulo de bateria UPS', 'quantity' => 2, 'unit_cost' => 300, 'total_cost' => 600],
            ['maintenance_case_id' => $createdCases['MC-2026-001']->id, 'item_type' => MaintenanceItemType::SERVICIO, 'name' => 'Mano de obra tecnica', 'description' => 'Diagnostico y calibracion', 'quantity' => 1, 'unit_cost' => 250, 'total_cost' => 250],
            ['maintenance_case_id' => $createdCases['MC-2026-002']->id, 'item_type' => MaintenanceItemType::INSUMO, 'name' => 'Kit de limpieza', 'description' => 'Alcohol isopropilico y aire comprimido', 'quantity' => 1, 'unit_cost' => 45, 'total_cost' => 45],
        ];

        foreach ($items as $item) {
            MaintenanceItem::updateOrCreate(
                ['maintenance_case_id' => $item['maintenance_case_id'], 'name' => $item['name']],
                $item
            );
        }

        $campaignAssets = [
            ['campaign_id' => $campaign->id, 'asset_id' => $assets['EQ-TES-002']->id, 'assigned_technician_id' => $employees['70345678']->id, 'scheduled_date' => now()->subDays(10)->toDateString(), 'attended_date' => now()->subDays(9)->toDateString(), 'status' => CampaignAssetStatus::ATENDIDO, 'attended_type' => 'manual', 'checklist_tasks' => ['sopleteado_limpieza', 'pasta_termica'], 'maintenance_case_id' => null, 'notes' => 'Mantenimiento preventivo en oficina completado.'],
            ['campaign_id' => $campaign->id, 'asset_id' => $assets['EQ-OTI-001']->id, 'assigned_technician_id' => $employees['70234567']->id, 'scheduled_date' => now()->addDays(3)->toDateString(), 'attended_date' => null, 'status' => CampaignAssetStatus::PROGRAMADO, 'maintenance_case_id' => null, 'notes' => 'Programado para revision de bateria.'],
            ['campaign_id' => $campaign->id, 'asset_id' => $assets['EQ-ADM-003']->id, 'assigned_technician_id' => $employees['70345678']->id, 'scheduled_date' => now()->addDays(5)->toDateString(), 'attended_date' => null, 'status' => CampaignAssetStatus::PENDIENTE, 'maintenance_case_id' => null, 'notes' => 'Pendiente confirmacion con el area usuaria.'],
        ];

        foreach ($campaignAssets as $campaignAsset) {
            CampaignAsset::updateOrCreate(
                ['campaign_id' => $campaignAsset['campaign_id'], 'asset_id' => $campaignAsset['asset_id']],
                $campaignAsset
            );
        }
    }
}
