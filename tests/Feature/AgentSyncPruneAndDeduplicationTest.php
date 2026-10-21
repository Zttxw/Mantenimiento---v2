<?php

namespace Tests\Feature;

use App\DTOs\AgentDevice\HeartbeatDTO;
use App\DTOs\AgentSync\CreateAgentSyncDTO;
use App\Enums\AgentSyncType;
use App\Enums\RoleEnum;
use App\Enums\SettingType;
use App\Models\AgentDevice;
use App\Models\AgentSync;
use App\Models\Setting;
use App\Models\User;
use App\Services\AgentDevice\AgentDeviceService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgentSyncPruneAndDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    private AgentDeviceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AgentDeviceService::class);
    }

    public function test_consecutive_identical_snapshots_do_not_insert_new_rows(): void
    {
        $device = AgentDevice::create([
            'hostname' => 'PC-TEST-01',
            'status' => 'activo',
        ]);

        $payload1 = [
            'collectedAtUtc' => '2026-10-20T10:00:00+00:00',
            'device' => ['hostname' => 'PC-TEST-01', 'totalMemoryGb' => 16],
            'installedSoftware' => [['name' => 'Chrome', 'version' => '120.0']],
        ];

        // 1. Primer snapshot: se debe insertar
        $dto1 = CreateAgentSyncDTO::fromArray([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => $payload1,
            'detected_changes_json' => null,
        ]);
        $sync1 = $this->service->receiveSync($device, $dto1);

        $this->assertNotNull($sync1);
        $this->assertEquals(1, $device->syncs()->count());
        $this->assertEquals('initial_registration', $sync1->detected_changes_json['type']);

        // 2. Segundo snapshot con diferente collectedAtUtc pero contenido estructural identico
        $payload2 = $payload1;
        $payload2['collectedAtUtc'] = '2026-10-20T11:00:00+00:00';

        $dto2 = CreateAgentSyncDTO::fromArray([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => $payload2,
            'detected_changes_json' => null,
        ]);
        $sync2 = $this->service->receiveSync($device, $dto2);

        $this->assertNull($sync2, 'No debe insertar una fila cuando el hash estructural es identico');
        $this->assertEquals(1, $device->syncs()->count(), 'El conteo de filas debe mantenerse en 1');
        $this->assertEquals($payload2, $device->fresh()->last_snapshot_json, 'last_snapshot_json debe actualizarse siempre');

        // 3. Tercer snapshot con cambio real de RAM
        $payload3 = $payload2;
        $payload3['device']['totalMemoryGb'] = 32;

        $dto3 = CreateAgentSyncDTO::fromArray([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => $payload3,
            'detected_changes_json' => null,
        ]);
        $sync3 = $this->service->receiveSync($device, $dto3);

        $this->assertNotNull($sync3);
        $this->assertEquals(2, $device->syncs()->count(), 'Debe insertarse una nueva fila cuando hay cambio estructural');
        $this->assertEquals(16, $sync3->detected_changes_json['hardware']['totalMemoryGb']['from']);
        $this->assertEquals(32, $sync3->detected_changes_json['hardware']['totalMemoryGb']['to']);
    }

    public function test_purge_command_preserves_latest_snapshot_per_device_even_if_old(): void
    {
        $device1 = AgentDevice::create(['hostname' => 'PC-OLD-01', 'status' => 'activo']);
        $device2 = AgentDevice::create(['hostname' => 'PC-OLD-02', 'status' => 'activo']);

        // Device 1 tiene dos snapshots antiguos (120 días y 100 días atrás)
        $d1Snap1 = AgentSync::create([
            'agent_device_id' => $device1->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => ['v' => 1],
            'status' => 'procesado',
            'synced_at' => now()->subDays(120),
        ]);
        DB::table('agent_syncs')->where('id', $d1Snap1->id)->update([
            'created_at' => now()->subDays(120),
            'synced_at' => now()->subDays(120),
        ]);

        $d1Snap2 = AgentSync::create([ // Este es su último snapshot
            'agent_device_id' => $device1->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => ['v' => 2],
            'status' => 'procesado',
            'synced_at' => now()->subDays(100),
        ]);
        DB::table('agent_syncs')->where('id', $d1Snap2->id)->update([
            'created_at' => now()->subDays(100),
            'synced_at' => now()->subDays(100),
        ]);

        // Device 2 solo tiene un snapshot antiguo (150 días atrás)
        $d2SnapOnly = AgentSync::create([ // Este es su único y último snapshot
            'agent_device_id' => $device2->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => ['v' => 1],
            'status' => 'procesado',
            'synced_at' => now()->subDays(150),
        ]);
        DB::table('agent_syncs')->where('id', $d2SnapOnly->id)->update([
            'created_at' => now()->subDays(150),
            'synced_at' => now()->subDays(150),
        ]);

        // Heartbeats antiguos (15 días atrás) y recientes (2 días atrás)
        $oldHeartbeat = AgentSync::create([
            'agent_device_id' => $device1->id,
            'sync_type' => AgentSyncType::HEARTBEAT->value,
            'payload_json' => ['status' => 'ok'],
            'status' => 'procesado',
            'synced_at' => now()->subDays(15),
        ]);
        DB::table('agent_syncs')->where('id', $oldHeartbeat->id)->update([
            'created_at' => now()->subDays(15),
            'synced_at' => now()->subDays(15),
        ]);

        $recentHeartbeat = AgentSync::create([
            'agent_device_id' => $device1->id,
            'sync_type' => AgentSyncType::HEARTBEAT->value,
            'payload_json' => ['status' => 'ok'],
            'status' => 'procesado',
            'synced_at' => now()->subDays(2),
        ]);
        DB::table('agent_syncs')->where('id', $recentHeartbeat->id)->update([
            'created_at' => now()->subDays(2),
            'synced_at' => now()->subDays(2),
        ]);

        // Ejecutar comando con retención 7 días heartbeats y 90 días snapshots
        $this->artisan('agents:purge-sync-history', [
            '--heartbeat-days' => 7,
            '--snapshot-days' => 90,
            '--optimize' => true,
        ])->assertSuccessful();

        // Verificaciones de Heartbeats:
        $this->assertDatabaseMissing('agent_syncs', ['id' => $oldHeartbeat->id]);
        $this->assertDatabaseHas('agent_syncs', ['id' => $recentHeartbeat->id]);

        // Verificaciones de Snapshots:
        // d1Snap1 tiene 120 días y NO es el último de device1 -> debe haberse purgado
        $this->assertDatabaseMissing('agent_syncs', ['id' => $d1Snap1->id]);

        // d1Snap2 tiene 100 días pero ES el último de device1 -> DEBE PRESERVARSE
        $this->assertDatabaseHas('agent_syncs', ['id' => $d1Snap2->id]);

        // d2SnapOnly tiene 150 días pero ES el único/último de device2 -> DEBE PRESERVARSE
        $this->assertDatabaseHas('agent_syncs', ['id' => $d2SnapOnly->id]);
    }

    public function test_purge_command_respects_settings_table_and_dry_run(): void
    {
        Setting::create([
            'key' => 'agents.heartbeat_retention_days',
            'value' => '5',
            'type' => SettingType::INTEGER,
            'group_name' => 'agents',
        ]);

        $device = AgentDevice::create(['hostname' => 'PC-TEST', 'status' => 'activo']);

        $sync = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::HEARTBEAT->value,
            'payload_json' => ['status' => 'ok'],
            'status' => 'procesado',
            'synced_at' => now()->subDays(6),
        ]);
        DB::table('agent_syncs')->where('id', $sync->id)->update([
            'created_at' => now()->subDays(6),
            'synced_at' => now()->subDays(6),
        ]);

        // Con dry-run no se debe borrar nada
        $this->artisan('agents:purge-sync-history', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('agent_syncs', ['id' => $sync->id]);

        // Sin dry-run toma los 5 días configurados en settings y lo borra
        $this->artisan('agents:purge-sync-history')
            ->assertSuccessful();

        $this->assertDatabaseMissing('agent_syncs', ['id' => $sync->id]);
    }

    public function test_web_show_loads_and_renders_view_without_errors(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::create([
            'username' => 'admin_test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole(RoleEnum::ADMIN->value);

        $device = AgentDevice::create([
            'hostname' => 'PC-MONITOR-01',
            'status' => 'activo',
            'agent_version' => '1.0.0',
            'last_ip' => '192.168.1.50',
            'last_heartbeat_at' => now(),
        ]);

        // Crear syncs de heartbeat y snapshot para verificar que la vista renderiza con datos
        AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::HEARTBEAT->value,
            'payload_json' => [
                'last_ip' => '192.168.1.50',
                'agent_version' => '1.0.0',
                'health' => ['overallStatus' => 'ok'],
            ],
            'status' => 'procesado',
            'synced_at' => now()->subMinutes(10),
        ]);

        AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => [
                'device' => ['hostname' => 'PC-MONITOR-01', 'totalMemoryGb' => 16],
            ],
            'status' => 'procesado',
            'synced_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->get(route('agents.show', $device));

        $response->assertOk();
        $response->assertViewIs('agents.show');
        $response->assertSee('PC-MONITOR-01');
        $response->assertSee('192.168.1.50');
    }

    public function test_purge_command_with_compact_snapshots_preserves_first_of_streak_and_latest(): void
    {
        $device = AgentDevice::create(['hostname' => 'PC-COMPACT-01', 'status' => 'activo']);

        $payloadA = [
            'device' => ['hostname' => 'PC-COMPACT-01', 'totalMemoryGb' => 16],
            'installedSoftware' => [['name' => 'AppA', 'version' => '1.0']],
        ];

        // 4 snapshots idénticos con diferentes fechas (dentro del período de retención)
        $s1 = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => array_merge($payloadA, ['collectedAtUtc' => '2026-10-15T10:00:00+00:00']),
            'status' => 'procesado',
            'synced_at' => now()->subDays(5),
        ]);
        $s2 = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => array_merge($payloadA, ['collectedAtUtc' => '2026-10-16T10:00:00+00:00']),
            'status' => 'procesado',
            'synced_at' => now()->subDays(4),
        ]);
        $s3 = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => array_merge($payloadA, ['collectedAtUtc' => '2026-10-17T10:00:00+00:00']),
            'status' => 'procesado',
            'synced_at' => now()->subDays(3),
        ]);
        $s4 = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => array_merge($payloadA, ['collectedAtUtc' => '2026-10-18T10:00:00+00:00']),
            'status' => 'procesado',
            'synced_at' => now()->subDays(2),
        ]);

        // Asegurar orden temporal en BD
        DB::table('agent_syncs')->where('id', $s1->id)->update(['created_at' => now()->subDays(5)]);
        DB::table('agent_syncs')->where('id', $s2->id)->update(['created_at' => now()->subDays(4)]);
        DB::table('agent_syncs')->where('id', $s3->id)->update(['created_at' => now()->subDays(3)]);
        DB::table('agent_syncs')->where('id', $s4->id)->update(['created_at' => now()->subDays(2)]);

        // 1. Dry run con compact-snapshots no borra nada
        $this->artisan('agents:purge-sync-history', [
            '--compact-snapshots' => true,
            '--dry-run' => true,
            '--snapshot-days' => 90,
        ])->assertSuccessful();

        $this->assertEquals(4, AgentSync::where('agent_device_id', $device->id)->count());

        // 2. Ejecución real con compact-snapshots
        $this->artisan('agents:purge-sync-history', [
            '--compact-snapshots' => true,
            '--snapshot-days' => 90,
        ])->assertSuccessful();

        // Debe conservar:
        // - s1 (el primero del tramo idéntico)
        // - s4 (el último snapshot del dispositivo)
        // Y eliminar los duplicados intermedios s2 y s3
        $this->assertDatabaseHas('agent_syncs', ['id' => $s1->id]);
        $this->assertDatabaseMissing('agent_syncs', ['id' => $s2->id]);
        $this->assertDatabaseMissing('agent_syncs', ['id' => $s3->id]);
        $this->assertDatabaseHas('agent_syncs', ['id' => $s4->id]);
        $this->assertEquals(2, AgentSync::where('agent_device_id', $device->id)->count());
    }

    public function test_routine_heartbeat_updates_device_without_creating_sync_history_row(): void
    {
        $device = AgentDevice::create(['hostname' => 'PC-01', 'status' => 'activo']);
        $dto = HeartbeatDTO::fromArray([
            'agent_version' => '1.0.0',
            'status' => 'ok',
            'sent_at_utc' => now()->toIso8601String(),
            'last_ip' => '192.168.1.50',
            'health' => [
                'overallStatus' => 'ok',
                'warnings' => [],
            ],
        ]);

        $updated = $this->service->heartbeat($device, $dto);

        $this->assertEquals('192.168.1.50', $updated->last_ip);
        $this->assertNotNull($updated->last_heartbeat_at);
        $this->assertEquals(0, AgentSync::where('agent_device_id', $device->id)->count());
    }

    public function test_heartbeat_with_health_alert_or_warning_creates_sync_history_row(): void
    {
        $device = AgentDevice::create(['hostname' => 'PC-02', 'status' => 'activo']);
        $dto = HeartbeatDTO::fromArray([
            'agent_version' => '1.0.0',
            'status' => 'ok',
            'sent_at_utc' => now()->toIso8601String(),
            'last_ip' => '192.168.1.51',
            'health' => [
                'overallStatus' => 'critical',
                'warnings' => ['critical: disco C:\ en estado critico.'],
            ],
        ]);

        $updated = $this->service->heartbeat($device, $dto);

        $this->assertEquals('192.168.1.51', $updated->last_ip);
        $this->assertEquals(1, AgentSync::where('agent_device_id', $device->id)->count());
        $sync = AgentSync::where('agent_device_id', $device->id)->first();
        $this->assertEquals(AgentSyncType::HEARTBEAT, $sync->sync_type);
        $this->assertEquals('critical', $sync->payload_json['health']['overallStatus']);
    }

    public function test_purge_eliminates_routine_heartbeats_but_preserves_alerts_and_valid_snapshots(): void
    {
        $device = AgentDevice::create(['hostname' => 'PC-03', 'status' => 'activo']);

        // 1. Heartbeat viejo rutinario (debe purgarse)
        $oldRoutineHb = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::HEARTBEAT->value,
            'payload_json' => [
                'status' => 'ok',
                'health' => ['overallStatus' => 'ok', 'warnings' => []],
            ],
            'status' => 'procesado',
            'synced_at' => now()->subDays(20),
        ]);
        DB::table('agent_syncs')->where('id', $oldRoutineHb->id)->update(['created_at' => now()->subDays(20)]);

        // 2. Heartbeat viejo con alerta crítica (DEBE PRESERVARSE)
        $oldAlertHb = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::HEARTBEAT->value,
            'payload_json' => [
                'status' => 'ok',
                'health' => ['overallStatus' => 'critical', 'warnings' => ['disco lleno']],
            ],
            'status' => 'procesado',
            'synced_at' => now()->subDays(20),
        ]);
        DB::table('agent_syncs')->where('id', $oldAlertHb->id)->update(['created_at' => now()->subDays(20)]);

        // 3. Snapshot antiguo único/vigente (DEBE PRESERVARSE)
        $oldLatestSnap = AgentSync::create([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => ['device' => ['hostname' => 'PC-03']],
            'status' => 'procesado',
            'synced_at' => now()->subDays(150),
        ]);
        DB::table('agent_syncs')->where('id', $oldLatestSnap->id)->update(['created_at' => now()->subDays(150)]);

        // Ejecutar comando con retención 7 días de heartbeats
        $this->artisan('agents:purge-sync-history', [
            '--heartbeat-days' => 7,
            '--snapshot-days' => 90,
        ])->assertSuccessful();

        // Verificaciones:
        // El rutinario debe haber sido eliminado
        $this->assertDatabaseMissing('agent_syncs', ['id' => $oldRoutineHb->id]);

        // La alerta y el snapshot vigente DEBEN PRESERVARSE
        $this->assertDatabaseHas('agent_syncs', ['id' => $oldAlertHb->id]);
        $this->assertDatabaseHas('agent_syncs', ['id' => $oldLatestSnap->id]);
    }
}
