<?php

namespace Tests\Feature;

use App\DTOs\AgentDevice\HeartbeatDTO;
use App\Enums\CampaignAssetStatus;
use App\Enums\CampaignStatus;
use App\Models\AgentDevice;
use App\Models\Asset;
use App\Models\CampaignAsset;
use App\Models\Employee;
use App\Models\MaintenanceCampaign;
use App\Models\MaintenanceCase;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\AgentDevice\AgentDeviceService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CampaignIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $coordinador;

    private User $tecnicoUser;

    private Employee $tecnicoEmployee;

    private OrganizationalUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->unit = OrganizationalUnit::create([
            'type' => 'oficina',
            'code' => 'TEST-OTI',
            'name' => 'Oficina de Tecnologías de la Información',
            'is_active' => true,
        ]);

        $this->tecnicoEmployee = Employee::create([
            'dni' => '88889999',
            'name' => 'Técnico',
            'last_name' => 'Campo',
            'full_name' => 'Técnico Campo',
            'organizational_unit_id' => $this->unit->id,
            'is_technician' => true,
            'is_active' => true,
        ]);

        $this->tecnicoUser = User::create([
            'username' => 'tecnico_test',
            'email' => 'tecnico@mdsj.local',
            'password' => bcrypt('password'),
            'employee_id' => $this->tecnicoEmployee->id,
            'is_active' => true,
        ]);
        $this->tecnicoUser->assignRole('tecnico');

        $this->coordinador = User::create([
            'username' => 'coordinador_test',
            'email' => 'coordinador@mdsj.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->coordinador->assignRole('coordinador_oti');
    }

    public function test_campaign_only_accepts_manual_attendance_when_status_is_en_curso(): void
    {
        $campaign = MaintenanceCampaign::create([
            'code' => 'CAMP-TEST-01',
            'name' => 'Campaña Preventiva Q1',
            'start_date' => now()->toDateString(),
            'status' => CampaignStatus::PLANIFICADA->value,
            'created_by' => $this->coordinador->id,
        ]);

        $asset = Asset::create([
            'uuid' => (string) Str::uuid(),
            'internal_code' => 'EQ-PRN-001',
            'name' => 'Impresora EPSON L3150',
            'asset_type' => 'impresora',
            'status' => 'en_uso',
            'condition' => 'bueno',
            'organizational_unit_id' => $this->unit->id,
        ]);

        $campaignAsset = CampaignAsset::create([
            'campaign_id' => $campaign->id,
            'asset_id' => $asset->id,
            'status' => CampaignAssetStatus::PENDIENTE->value,
        ]);

        // Intentar registrar atención cuando está "planificada" (no en_curso)
        $response = $this->actingAs($this->tecnicoUser)
            ->post(route('campaigns.assets.attend', [$campaign, $campaignAsset]), [
                'attended_date' => now()->toDateString(),
                'attended_by_employee_id' => $this->tecnicoEmployee->id,
                'checklist_tasks' => ['sopleteado_limpieza'],
            ]);

        $response->assertSessionHas('error');
        $this->assertEquals(CampaignAssetStatus::PENDIENTE, $campaignAsset->fresh()->status);

        // Ahora activar la campaña a "en_curso"
        $campaign->update(['status' => CampaignStatus::EN_CURSO->value]);

        $successResponse = $this->actingAs($this->tecnicoUser)
            ->post(route('campaigns.assets.attend', [$campaign, $campaignAsset]), [
                'patrimonial_code' => '74089500099',
                'attended_date' => now()->toDateString(),
                'attended_by_employee_id' => $this->tecnicoEmployee->id,
                'checklist_tasks' => ['sopleteado_limpieza', 'limpieza_rodillos_impresion'],
                'asset_condition_after' => 'bueno',
                'notes' => 'Rodillos lubricados y prueba de inyectores OK.',
            ]);

        $successResponse->assertSessionHas('success');

        $freshCa = $campaignAsset->fresh();
        $this->assertEquals(CampaignAssetStatus::ATENDIDO, $freshCa->status);
        $this->assertEquals('manual', $freshCa->attended_type);
        $this->assertEquals($this->tecnicoEmployee->id, $freshCa->attended_by_employee_id);
        $this->assertContains('limpieza_rodillos_impresion', $freshCa->checklist_tasks);

        // Verificar que el sticker patrimonial del Asset físico se actualizó en caliente
        $this->assertEquals('74089500099', $asset->fresh()->patrimonial_code);

        // REGLA FUNDAMENTAL: Ningún caso en maintenance_cases debió ser creado
        $this->assertEquals(0, MaintenanceCase::count());
    }

    public function test_agent_heartbeat_auto_attends_asset_in_active_campaign_without_touching_cases(): void
    {
        $campaign = MaintenanceCampaign::create([
            'code' => 'CAMP-TEST-02',
            'name' => 'Campaña Activa San Jerónimo',
            'start_date' => now()->toDateString(),
            'status' => CampaignStatus::EN_CURSO->value,
            'created_by' => $this->coordinador->id,
        ]);

        $pcAsset = Asset::create([
            'uuid' => (string) Str::uuid(),
            'internal_code' => 'PC-TESORERIA-01',
            'name' => 'PC Dell OptiPlex 3080',
            'asset_type' => 'computadora',
            'status' => 'en_uso',
            'condition' => 'bueno',
            'organizational_unit_id' => $this->unit->id,
        ]);

        $ca = CampaignAsset::create([
            'campaign_id' => $campaign->id,
            'asset_id' => $pcAsset->id,
            'status' => CampaignAssetStatus::PENDIENTE->value,
        ]);

        $device = AgentDevice::create([
            'uuid' => (string) Str::uuid(),
            'asset_id' => $pcAsset->id,
            'hostname' => 'PC-TES-01',
            'os_name' => 'Windows 11 Pro',
            'agent_version' => '2.1.0',
            'status' => 'activo',
            'api_token' => hash('sha256', 'dummy-token'),
        ]);

        $dto = new HeartbeatDTO(
            sent_at_utc: now()->toIso8601String(),
            agent_version: '2.1.0',
            status: 'ok',
            last_ip: '192.168.1.150',
            health: ['overallStatus' => 'OK', 'warnings' => []],
            asset_code: 'PC-TESORERIA-01',
            organizational_unit_id: $this->unit->id,
            responsible_employee_id: $this->tecnicoEmployee->id,
            last_snapshot_json: null
        );

        $service = app(AgentDeviceService::class);
        $service->heartbeat($device, $dto);

        // Verificar que la campaña registró la atención automática por agente
        $freshCa = $ca->fresh();
        $this->assertEquals(CampaignAssetStatus::ATENDIDO, $freshCa->status);
        $this->assertEquals('agente', $freshCa->attended_type);
        $this->assertStringContainsString('Agente SIGAT', $freshCa->notes);

        // Ningún ticket de helpdesk debió ser creado
        $this->assertEquals(0, MaintenanceCase::count());
    }

    public function test_quick_register_asset_in_field_attaches_to_campaign(): void
    {
        $campaign = MaintenanceCampaign::create([
            'code' => 'CAMP-TEST-03',
            'name' => 'Campaña Descentralizada',
            'start_date' => now()->toDateString(),
            'status' => CampaignStatus::EN_CURSO->value,
            'created_by' => $this->coordinador->id,
        ]);

        $response = $this->actingAs($this->tecnicoUser)
            ->post(route('campaigns.assets.quick-register', $campaign), [
                'name' => 'Monitor LG 24" IPS',
                'asset_type' => 'monitor',
                'patrimonial_code' => '74089500055',
                'brand' => 'LG',
                'model' => '24MK430H',
                'organizational_unit_id' => $this->unit->id,
                'assigned_technician_id' => $this->tecnicoEmployee->id,
                'mark_attended' => 1,
                'checklist_tasks' => ['sopleteado_limpieza', 'revision_cableado_fuentes'],
                'notes' => 'Monitor hallado en recepción, sticker colocado.',
            ]);

        $response->assertSessionHas('success');

        $discoveredAsset = Asset::where('patrimonial_code', '74089500055')->first();
        $this->assertNotNull($discoveredAsset);
        $this->assertEquals('Monitor LG 24" IPS', $discoveredAsset->name);

        $ca = CampaignAsset::where('campaign_id', $campaign->id)
            ->where('asset_id', $discoveredAsset->id)
            ->first();

        $this->assertNotNull($ca);
        $this->assertEquals(CampaignAssetStatus::ATENDIDO, $ca->status);
        $this->assertEquals('manual', $ca->attended_type);

        // Cero interferencia con Helpdesk
        $this->assertEquals(0, MaintenanceCase::count());
    }

    public function test_jefe_oti_can_approve_campaign_from_pendiente_aprobacion(): void
    {
        $jefeUser = User::create([
            'username' => 'jefe_test',
            'email' => 'jefe@mdsj.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $jefeUser->assignRole('jefe_oti');

        $campaign = MaintenanceCampaign::create([
            'code' => 'CAMP-TEST-APPROVAL',
            'name' => 'Campaña Pendiente de Aprobación',
            'start_date' => now()->toDateString(),
            'status' => CampaignStatus::PENDIENTE_APROBACION->value,
            'created_by' => $this->tecnicoUser->id,
        ]);

        // Jefe aprueba y activa la campaña
        $response = $this->actingAs($jefeUser)
            ->patch(route('campaigns.status', $campaign), [
                'status' => CampaignStatus::EN_CURSO->value,
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(CampaignStatus::EN_CURSO, $campaign->fresh()->status);
    }

    public function test_acta_de_mantenimiento_generates_successfully_from_campaign_assets(): void
    {
        $campaign = MaintenanceCampaign::create([
            'code' => 'CAMP-ACTA-01',
            'name' => 'Campaña Acta de Mantenimiento',
            'start_date' => now()->toDateString(),
            'status' => CampaignStatus::EN_CURSO->value,
            'created_by' => $this->coordinador->id,
        ]);

        $asset = Asset::create([
            'uuid' => (string) Str::uuid(),
            'internal_code' => 'EQ-SRV-01',
            'patrimonial_code' => '74089500077',
            'name' => 'Servidor ProLiant',
            'asset_type' => 'servidor',
            'status' => 'en_uso',
            'condition' => 'bueno',
            'organizational_unit_id' => $this->unit->id,
        ]);

        CampaignAsset::create([
            'campaign_id' => $campaign->id,
            'asset_id' => $asset->id,
            'status' => CampaignAssetStatus::ATENDIDO->value,
            'attended_type' => 'manual',
            'attended_date' => now()->toDateString(),
            'attended_by_employee_id' => $this->tecnicoEmployee->id,
            'checklist_tasks' => ['sopleteado_limpieza', 'revision_cableado_fuentes'],
            'notes' => 'Mantenimiento preventivo anual concluido.',
        ]);

        Storage::fake('local');

        $response = $this->actingAs($this->coordinador)
            ->post(route('campaigns.generate-acta', $campaign), [
                'unit_id' => $this->unit->id,
            ]);

        $response->assertStatus(200);
        $this->assertEquals(0, MaintenanceCase::count());
    }

    public function test_campaign_show_view_renders_successfully_with_cloaked_modals_and_assets(): void
    {
        $campaign = MaintenanceCampaign::create([
            'code' => 'CAMP-SHOW-01',
            'name' => 'Campaña Pruebas Renderizado',
            'type' => 'preventivo',
            'status' => CampaignStatus::EN_CURSO->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'created_by' => $this->coordinador->id,
        ]);

        $asset = Asset::create([
            'uuid' => (string) Str::uuid(),
            'internal_code' => 'EQ-SHOW-01',
            'patrimonial_code' => '74089999001',
            'name' => 'Computadora Dell Optiplex',
            'asset_type' => 'computadora',
            'status' => 'en_uso',
            'condition' => 'bueno',
            'organizational_unit_id' => $this->unit->id,
        ]);

        CampaignAsset::create([
            'campaign_id' => $campaign->id,
            'asset_id' => $asset->id,
            'status' => CampaignAssetStatus::PENDIENTE->value,
        ]);

        $response = $this->actingAs($this->coordinador)
            ->get(route('campaigns.show', $campaign));

        $response->assertStatus(200);
        $response->assertSee('Campaña Pruebas Renderizado');
        $response->assertSee('CAMP-SHOW-01');
        $response->assertSee('[x-cloak]');
    }
}
