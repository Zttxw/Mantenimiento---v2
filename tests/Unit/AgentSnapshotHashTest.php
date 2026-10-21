<?php

namespace Tests\Unit;

use App\Services\AgentDevice\AgentDeviceService;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

class AgentSnapshotHashTest extends PhpUnitTestCase
{
    private AgentDeviceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Instanciamos el servicio directamente sin dependencias complejas para test unitario puro
        $this->service = new AgentDeviceService();
    }

    public function test_hash_is_identical_when_only_volatile_fields_change(): void
    {
        $payload1 = [
            'collectedAtUtc' => '2026-10-20T10:00:00.1234567+00:00',
            'device' => [
                'hostname' => 'PC_OTI_01',
                'operatingSystem' => 'Windows 11 Pro',
                'totalMemoryGb' => 16,
                'totalStorageGb' => 512,
                'freeStorageGb' => 200.5,
                'lastBootTimeUtc' => '2026-10-20T08:00:00+00:00',
                'storageVolumes' => [
                    ['name' => 'C:\\', 'totalGb' => 512, 'freeGb' => 200.5, 'usedGb' => 311.5, 'usedPercent' => 60.8],
                ],
            ],
            'installedSoftware' => [
                ['name' => 'Google Chrome', 'version' => '120.0.1'],
                ['name' => '7-Zip', 'version' => '23.01'],
            ],
        ];

        $payload2 = [
            'collectedAtUtc' => '2026-10-21T18:45:12.9876543+00:00', // Modificado
            'device' => [
                'hostname' => 'PC_OTI_01',
                'operatingSystem' => 'Windows 11 Pro',
                'totalMemoryGb' => 16,
                'totalStorageGb' => 512,
                'freeStorageGb' => 189.2, // Modificado (espacio libre volatil)
                'lastBootTimeUtc' => '2026-10-21T07:30:00+00:00', // Modificado (reinicio)
                'storageVolumes' => [
                    ['name' => 'C:\\', 'totalGb' => 512, 'freeGb' => 189.2, 'usedGb' => 322.8, 'usedPercent' => 63.0],
                ],
            ],
            'installedSoftware' => [
                // Orden invertido intencionalmente
                ['name' => '7-Zip', 'version' => '23.01'],
                ['name' => 'Google Chrome', 'version' => '120.0.1'],
            ],
        ];

        $hash1 = $this->service->computeStructuralSnapshotHash($payload1);
        $hash2 = $this->service->computeStructuralSnapshotHash($payload2);

        $this->assertEquals($hash1, $hash2, 'El hash estructural debe ser identico si solo varian campos volatiles');
    }

    public function test_hash_changes_when_software_or_hardware_is_modified(): void
    {
        $basePayload = [
            'device' => [
                'hostname' => 'PC_OTI_01',
                'totalMemoryGb' => 16,
            ],
            'installedSoftware' => [
                ['name' => 'Google Chrome', 'version' => '120.0.1'],
            ],
        ];

        $modifiedRam = $basePayload;
        $modifiedRam['device']['totalMemoryGb'] = 32;

        $modifiedSoftware = $basePayload;
        $modifiedSoftware['installedSoftware'][] = ['name' => 'VS Code', 'version' => '1.85'];

        $baseHash = $this->service->computeStructuralSnapshotHash($basePayload);
        $ramHash = $this->service->computeStructuralSnapshotHash($modifiedRam);
        $softwareHash = $this->service->computeStructuralSnapshotHash($modifiedSoftware);

        $this->assertNotEquals($baseHash, $ramHash, 'El hash debe cambiar al modificar memoria RAM');
        $this->assertNotEquals($baseHash, $softwareHash, 'El hash debe cambiar al instalar nuevo software');
    }

    public function test_delta_detects_software_and_hardware_changes(): void
    {
        $oldPayload = [
            'device' => [
                'totalMemoryGb' => 16,
                'operatingSystem' => 'Windows 10 Pro',
            ],
            'installedSoftware' => [
                ['name' => 'Chrome', 'version' => '119.0'],
                ['name' => 'WinRAR', 'version' => '6.0'],
            ],
        ];

        $newPayload = [
            'device' => [
                'totalMemoryGb' => 32,
                'operatingSystem' => 'Windows 11 Pro',
            ],
            'installedSoftware' => [
                ['name' => 'Chrome', 'version' => '120.0'], // Actualizado
                ['name' => '7-Zip', 'version' => '23.01'],  // Nuevo
                // WinRAR eliminado
            ],
        ];

        $delta = $this->service->calculateSnapshotDelta($oldPayload, $newPayload);

        $this->assertEquals(16, $delta['hardware']['totalMemoryGb']['from']);
        $this->assertEquals(32, $delta['hardware']['totalMemoryGb']['to']);
        $this->assertEquals('Windows 10 Pro', $delta['hardware']['operatingSystem']['from']);
        $this->assertEquals('Windows 11 Pro', $delta['hardware']['operatingSystem']['to']);

        $this->assertEquals('7-Zip', $delta['software']['added'][0]['name']);
        $this->assertEquals('WinRAR', $delta['software']['removed'][0]['name']);
        $this->assertEquals('Chrome', $delta['software']['updated'][0]['name']);
        $this->assertEquals('119.0', $delta['software']['updated'][0]['from_version']);
        $this->assertEquals('120.0', $delta['software']['updated'][0]['to_version']);
    }
}
