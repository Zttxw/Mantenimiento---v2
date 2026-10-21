<?php

namespace App\Services\AgentDevice;

use App\DTOs\AgentDevice\HeartbeatDTO;
use App\DTOs\AgentDevice\RegisterAgentDTO;
use App\DTOs\AgentSync\CreateAgentSyncDTO;
use App\Enums\AgentDeviceStatus;
use App\Enums\AgentSyncType;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Exceptions\AgentDevice\AgentDeviceException;
use App\Models\AgentDevice;
use App\Models\Asset;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AgentDeviceService
{
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return AgentDevice::with('asset')
            ->byStatus($filters['status'] ?? null)
            ->latest('last_heartbeat_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function register(?Asset $asset, RegisterAgentDTO $dto): AgentDevice
    {
        if ($asset?->agentDevice()->exists()) {
            throw AgentDeviceException::assetAlreadyRegistered($asset->internal_code ?? $asset->name);
        }

        $plainToken = Str::random(60);
        $payload = array_merge($dto->toArray(), [
            'asset_id'            => $asset?->id,
            'status'             => AgentDeviceStatus::ACTIVO->value,
            'last_heartbeat_at'  => now(),
            'api_token'           => hash('sha256', $plainToken),
        ]);

        $device = AgentDevice::create($payload);
        $device->setAttribute('plain_api_token', $plainToken);

        return $device;
    }

    public function heartbeat(AgentDevice $device, HeartbeatDTO $dto): AgentDevice
    {
        $device->update([
            'agent_version'      => $dto->agent_version,
            'last_ip'            => $dto->last_ip,
            'last_heartbeat_at'  => now(),
            'status'             => AgentDeviceStatus::ACTIVO->value,
            'last_snapshot_json' => $dto->last_snapshot_json ?? $device->last_snapshot_json,
            'last_health_json'   => $dto->health ?? $device->last_health_json,
        ]);

        if ($device->asset && $dto->health) {
            $this->updateAssetFromAgentData($device->asset, null, $dto->health, $device);
        }

        if ($dto->asset_code || $dto->organizational_unit_id || $dto->responsible_employee_id) {
            $this->bindAsset(
                $device,
                $dto->asset_code,
                $dto->organizational_unit_id,
                $dto->responsible_employee_id,
                $dto->last_snapshot_json
            );
        }

        $device->syncs()->create([
            'sync_type'  => AgentSyncType::HEARTBEAT->value,
            'payload_json' => [
                'sent_at_utc' => $dto->sent_at_utc,
                'agent_version' => $dto->agent_version,
                'status' => $dto->status,
                'last_ip' => $dto->last_ip,
                'health' => $dto->health,
                'asset_code' => $dto->asset_code,
                'organizational_unit_id' => $dto->organizational_unit_id,
                'responsible_employee_id' => $dto->responsible_employee_id,
            ],
            'status'     => \App\Enums\AgentSyncStatus::PROCESADO->value,
            'synced_at'  => now(),
        ]);

        return $device->fresh();
    }

    public function receiveSync(AgentDevice $device, CreateAgentSyncDTO $dto): ?\App\Models\AgentSync
    {
        if ($dto->sync_type === AgentSyncType::SNAPSHOT && $dto->payload_json) {
            $newPayload = $dto->payload_json;
            $newHash = $this->computeStructuralSnapshotHash($newPayload);

            $previousSync = $device->syncs()
                ->where('sync_type', AgentSyncType::SNAPSHOT->value)
                ->latest('synced_at')
                ->first();

            $previousPayload = $previousSync?->payload_json ?? $device->last_snapshot_json;
            $previousHash = $previousPayload ? $this->computeStructuralSnapshotHash($previousPayload) : null;

            // Actualizar siempre last_snapshot_json en agent_devices
            $device->update(['last_snapshot_json' => $newPayload]);

            if ($device->asset) {
                $this->updateAssetFromAgentData($device->asset, $newPayload, $device->last_health_json, $device);
            }

            // Deduplicación: si el hash estructural es idéntico, no insertar nueva fila
            if ($previousHash !== null && $newHash === $previousHash) {
                return null;
            }

            // Si el hash difiere (o es el primer snapshot), calcular el delta
            $delta = $this->calculateSnapshotDelta($previousPayload ?? [], $newPayload);

            $sync = $device->syncs()->create([
                'sync_type'             => AgentSyncType::SNAPSHOT->value,
                'payload_json'          => $newPayload,
                'detected_changes_json' => $delta,
                'status'                => \App\Enums\AgentSyncStatus::PROCESADO->value,
                'synced_at'             => now(),
            ]);

            return $sync->fresh();
        }

        $sync = $device->syncs()->create($dto->toArray());
        $sync->update(['status' => \App\Enums\AgentSyncStatus::PROCESADO->value]);

        return $sync->fresh();
    }

    /**
     * Campos volátiles de telemetría/runtime ignorados en el hash estructural.
     */
    public function getIgnoredVolatileFields(): array
    {
        return [
            'collectedAtUtc',
            'CollectedAtUtc',
            'device.lastBootTimeUtc',
            'device.LastBootTimeUtc',
            'device.freeStorageGb',
            'device.FreeStorageGb',
            'device.storageVolumes.*.freeGb',
            'device.storageVolumes.*.usedGb',
            'device.storageVolumes.*.usedPercent',
            'device.storageVolumes.*.FreeGb',
            'device.storageVolumes.*.UsedGb',
            'device.storageVolumes.*.UsedPercent',
        ];
    }

    /**
     * Normaliza el snapshot eliminando campos volátiles y ordenando colecciones determinísticamente.
     */
    public function normalizeSnapshotPayload(array $payload): array
    {
        unset($payload['collectedAtUtc'], $payload['CollectedAtUtc']);

        if (isset($payload['device']) && is_array($payload['device'])) {
            unset(
                $payload['device']['lastBootTimeUtc'],
                $payload['device']['LastBootTimeUtc'],
                $payload['device']['freeStorageGb'],
                $payload['device']['FreeStorageGb']
            );

            if (isset($payload['device']['storageVolumes']) && is_array($payload['device']['storageVolumes'])) {
                $cleanedVolumes = [];
                foreach ($payload['device']['storageVolumes'] as $vol) {
                    if (is_array($vol)) {
                        unset(
                            $vol['freeGb'], $vol['usedGb'], $vol['usedPercent'],
                            $vol['FreeGb'], $vol['UsedGb'], $vol['UsedPercent']
                        );
                        $cleanedVolumes[] = $vol;
                    }
                }
                usort($cleanedVolumes, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
                $payload['device']['storageVolumes'] = $cleanedVolumes;
            }
        }

        if (isset($payload['installedSoftware']) && is_array($payload['installedSoftware'])) {
            $software = array_filter($payload['installedSoftware'], 'is_array');
            usort($software, function ($a, $b) {
                $nameA = strtolower($a['name'] ?? $a['normalizedName'] ?? '');
                $nameB = strtolower($b['name'] ?? $b['normalizedName'] ?? '');
                return strcmp($nameA, $nameB);
            });
            $payload['installedSoftware'] = array_values($software);
        }

        $this->recursiveKsort($payload);

        return $payload;
    }

    /**
     * Genera el hash SHA-256 del contenido estructural normalizado.
     */
    public function computeStructuralSnapshotHash(array $payload): string
    {
        $normalized = $this->normalizeSnapshotPayload($payload);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Calcula las diferencias estructurales (delta) entre dos snapshots.
     */
    public function calculateSnapshotDelta(array $oldPayload, array $newPayload): array
    {
        if (empty($oldPayload)) {
            $softwareCount = is_array($newPayload['installedSoftware'] ?? null)
                ? count($newPayload['installedSoftware'])
                : 0;

            return [
                'type'             => 'initial_registration',
                'detected_at'      => now()->toIso8601String(),
                'software_count'   => $softwareCount,
                'operating_system' => data_get($newPayload, 'device.operatingSystem'),
                'total_memory_gb'  => data_get($newPayload, 'device.totalMemoryGb'),
                'total_storage_gb' => data_get($newPayload, 'device.totalStorageGb'),
            ];
        }

        $delta = [
            'type'         => 'hardware_software_change',
            'detected_at'  => now()->toIso8601String(),
            'hardware'     => [],
            'software'     => [
                'added'   => [],
                'removed' => [],
                'updated' => [],
            ],
            'administrative' => [],
        ];

        // Comparar especificaciones de hardware
        $hwFields = ['operatingSystem', 'osVersion', 'processorName', 'totalMemoryGb', 'totalStorageGb', 'serialNumber'];
        foreach ($hwFields as $field) {
            $oldVal = data_get($oldPayload, "device.{$field}");
            $newVal = data_get($newPayload, "device.{$field}");
            if ($oldVal !== $newVal) {
                $delta['hardware'][$field] = ['from' => $oldVal, 'to' => $newVal];
            }
        }

        // Comparar software instalado
        $oldSoftware = $this->indexSoftware(data_get($oldPayload, 'installedSoftware', []));
        $newSoftware = $this->indexSoftware(data_get($newPayload, 'installedSoftware', []));

        foreach ($newSoftware as $name => $item) {
            if (! isset($oldSoftware[$name])) {
                $delta['software']['added'][] = [
                    'name'    => $item['name'],
                    'version' => $item['version'] ?? null,
                ];
            } elseif (($oldSoftware[$name]['version'] ?? '') !== ($item['version'] ?? '')) {
                $delta['software']['updated'][] = [
                    'name'         => $item['name'],
                    'from_version' => $oldSoftware[$name]['version'] ?? null,
                    'to_version'   => $item['version'] ?? null,
                ];
            }
        }

        foreach ($oldSoftware as $name => $item) {
            if (! isset($newSoftware[$name])) {
                $delta['software']['removed'][] = [
                    'name'    => $item['name'],
                    'version' => $item['version'] ?? null,
                ];
            }
        }

        // Comparar asignación administrativa
        $adminFields = ['assetCode', 'organizationalUnitId', 'responsibleEmployeeId'];
        foreach ($adminFields as $field) {
            $oldAdmin = data_get($oldPayload, "administrativeBinding.{$field}");
            $newAdmin = data_get($newPayload, "administrativeBinding.{$field}");
            if ($oldAdmin !== $newAdmin) {
                $delta['administrative'][$field] = ['from' => $oldAdmin, 'to' => $newAdmin];
            }
        }

        // Limpiar secciones vacías
        if (empty($delta['hardware'])) unset($delta['hardware']);
        if (empty($delta['software']['added']) && empty($delta['software']['removed']) && empty($delta['software']['updated'])) {
            unset($delta['software']);
        }
        if (empty($delta['administrative'])) unset($delta['administrative']);

        return $delta;
    }

    private function indexSoftware(mixed $list): array
    {
        if (! is_array($list)) return [];

        $indexed = [];
        foreach ($list as $item) {
            if (is_array($item)) {
                $name = trim($item['name'] ?? $item['normalizedName'] ?? '');
                if ($name !== '') {
                    $key = strtolower($name);
                    $indexed[$key] = [
                        'name'    => $name,
                        'version' => $item['version'] ?? null,
                    ];
                }
            }
        }

        return $indexed;
    }

    private function recursiveKsort(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveKsort($value);
            }
        }
    }

    public function findByToken(string $token): AgentDevice
    {
        $device = AgentDevice::where('api_token', hash('sha256', $token))->first();

        if (! $device) {
            throw AgentDeviceException::unauthorized();
        }

        return $device;
    }

    public function bindAsset(
        AgentDevice $device,
        ?string $assetCode,
        ?int $organizationalUnitId,
        ?int $responsibleEmployeeId,
        ?array $snapshot = null,
    ): AgentDevice {
        $asset = null;
        $assetCode = $assetCode ? trim($assetCode) : null;

        if ($assetCode) {
            $asset = Asset::query()
                ->where('internal_code', $assetCode)
                ->orWhere('patrimonial_code', $assetCode)
                ->first();
        }

        if (! $asset && $assetCode) {
            $asset = $this->createAssetFromAgentSnapshot(
                $assetCode,
                $device,
                $organizationalUnitId,
                $responsibleEmployeeId,
                $snapshot
            );
        }

        if (! $asset) {
            return $device->fresh();
        }

        if ($asset->agentDevice()->where('id', '!=', $device->id)->exists()) {
            throw AgentDeviceException::assetAlreadyRegistered($asset->internal_code ?? $asset->name);
        }

        $asset->update([
            'organizational_unit_id' => $organizationalUnitId ?? $asset->organizational_unit_id,
            'responsible_employee_id' => $responsibleEmployeeId ?? $asset->responsible_employee_id,
        ]);

        $this->updateAssetFromAgentData($asset, $snapshot, $device->last_health_json, $device);

        $device->update(['asset_id' => $asset->id]);

        return $device->fresh();
    }

    private function createAssetFromAgentSnapshot(
        string $assetCode,
        AgentDevice $device,
        ?int $organizationalUnitId,
        ?int $responsibleEmployeeId,
        ?array $snapshot,
    ): Asset {
        $hostname = data_get($snapshot, 'Device.Hostname')
            ?? data_get($snapshot, 'device.hostname')
            ?? $device->hostname;
        $manufacturer = data_get($snapshot, 'Device.Manufacturer')
            ?? data_get($snapshot, 'device.manufacturer');
        $model = data_get($snapshot, 'Device.Model')
            ?? data_get($snapshot, 'device.model')
            ?? $device->device_model;
        $serialNumber = data_get($snapshot, 'Device.SerialNumber')
            ?? data_get($snapshot, 'device.serialNumber')
            ?? data_get($snapshot, 'device.serial_number')
            ?? $device->serial_number;
        $operatingSystem = data_get($snapshot, 'Device.OperatingSystem')
            ?? data_get($snapshot, 'device.operatingSystem')
            ?? data_get($snapshot, 'device.operating_system')
            ?? $device->operating_system;

        return Asset::create([
            'internal_code' => mb_substr($assetCode, 0, 50),
            'patrimonial_code' => mb_substr($assetCode, 0, 50),
            'name' => $hostname ? "PC {$hostname}" : "PC {$assetCode}",
            'asset_type' => AssetType::COMPUTADORA->value,
            'brand' => $manufacturer ? mb_substr($manufacturer, 0, 100) : null,
            'model' => $model ? mb_substr($model, 0, 100) : null,
            'serial_number' => $serialNumber ? mb_substr($serialNumber, 0, 100) : null,
            'status' => AssetStatus::EN_USO->value,
            'condition' => AssetCondition::BUENO->value,
            'organizational_unit_id' => $organizationalUnitId,
            'responsible_employee_id' => $responsibleEmployeeId,
            'specs_json' => $this->buildAssetSpecsFromSnapshot($snapshot),
            'extra_json' => $this->buildAssetExtraFromAgentData($snapshot, null, $device),
            'notes' => 'Activo registrado automaticamente desde AgentSync.',
        ]);
    }

    private function updateAssetFromAgentData(
        Asset $asset,
        ?array $snapshot,
        ?array $health,
        AgentDevice $device,
    ): void {
        $updates = [
            'brand' => $this->limitString(
                data_get($snapshot, 'Device.Manufacturer')
                    ?? data_get($snapshot, 'device.manufacturer')
                    ?? $asset->brand,
                100
            ),
            'model' => $this->limitString(
                data_get($snapshot, 'Device.Model')
                    ?? data_get($snapshot, 'device.model')
                    ?? $asset->model,
                100
            ),
            'serial_number' => $this->limitString(
                data_get($snapshot, 'Device.SerialNumber')
                    ?? data_get($snapshot, 'device.serialNumber')
                    ?? data_get($snapshot, 'device.serial_number')
                    ?? $asset->serial_number,
                100
            ),
            'specs_json' => array_merge($asset->specs_json ?? [], $this->buildAssetSpecsFromSnapshot($snapshot)),
            'extra_json' => array_merge($asset->extra_json ?? [], $this->buildAssetExtraFromAgentData($snapshot, $health, $device)),
        ];

        $asset->update($updates);
    }

    private function buildAssetSpecsFromSnapshot(?array $snapshot): array
    {
        if (! $snapshot) {
            return [];
        }

        $software = data_get($snapshot, 'InstalledSoftware')
            ?? data_get($snapshot, 'installedSoftware')
            ?? [];

        $softwareNames = collect($software)
            ->map(fn ($item) => data_get($item, 'Name') ?? data_get($item, 'name'))
            ->filter()
            ->take(8)
            ->values()
            ->all();

        return array_filter([
            'hostname' => data_get($snapshot, 'Device.Hostname') ?? data_get($snapshot, 'device.hostname'),
            'cpu' => data_get($snapshot, 'Device.ProcessorName') ?? data_get($snapshot, 'device.processorName'),
            'ram_gb' => data_get($snapshot, 'Device.TotalMemoryGb') ?? data_get($snapshot, 'device.totalMemoryGb'),
            'total_storage_gb' => data_get($snapshot, 'Device.TotalStorageGb') ?? data_get($snapshot, 'device.totalStorageGb'),
            'free_storage_gb' => data_get($snapshot, 'Device.FreeStorageGb') ?? data_get($snapshot, 'device.freeStorageGb'),
            'storage_volumes' => data_get($snapshot, 'Device.StorageVolumes') ?? data_get($snapshot, 'device.storageVolumes'),
            'operating_system' => data_get($snapshot, 'Device.OperatingSystem') ?? data_get($snapshot, 'device.operatingSystem') ?? data_get($snapshot, 'device.operating_system'),
            'os_version' => data_get($snapshot, 'Device.OsVersion') ?? data_get($snapshot, 'device.osVersion'),
            'os_architecture' => data_get($snapshot, 'Device.OsArchitecture') ?? data_get($snapshot, 'device.osArchitecture'),
            'ip_addresses' => data_get($snapshot, 'Device.IpAddresses') ?? data_get($snapshot, 'device.ipAddresses'),
            'bios_version' => data_get($snapshot, 'Device.BiosVersion') ?? data_get($snapshot, 'device.biosVersion'),
            'motherboard_manufacturer' => data_get($snapshot, 'Device.MotherboardManufacturer') ?? data_get($snapshot, 'device.motherboardManufacturer'),
            'motherboard_product' => data_get($snapshot, 'Device.MotherboardProduct') ?? data_get($snapshot, 'device.motherboardProduct'),
            'motherboard_serial_number' => data_get($snapshot, 'Device.MotherboardSerialNumber') ?? data_get($snapshot, 'device.motherboardSerialNumber'),
            'domain' => data_get($snapshot, 'Device.Domain') ?? data_get($snapshot, 'device.domain'),
            'user_name' => data_get($snapshot, 'Device.UserName') ?? data_get($snapshot, 'device.userName'),
            'last_boot_time_utc' => data_get($snapshot, 'Device.LastBootTimeUtc') ?? data_get($snapshot, 'device.lastBootTimeUtc'),
            'software_count' => is_countable($software) ? count($software) : null,
            'top_software' => $softwareNames,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function buildAssetExtraFromAgentData(?array $snapshot, ?array $health, AgentDevice $device): array
    {
        $binding = data_get($snapshot, 'AdministrativeBinding')
            ?? data_get($snapshot, 'administrativeBinding')
            ?? [];

        return array_filter([
            'source' => 'agent',
            'agent_device_uuid' => $device->uuid,
            'collected_at_utc' => data_get($snapshot, 'CollectedAtUtc') ?? data_get($snapshot, 'collectedAtUtc'),
            'last_health' => $health,
            'asset_code' => data_get($binding, 'AssetCode') ?? data_get($binding, 'assetCode'),
            'organizational_unit_id' => data_get($binding, 'OrganizationalUnitId') ?? data_get($binding, 'organizationalUnitId'),
            'responsible_employee_id' => data_get($binding, 'ResponsibleEmployeeId') ?? data_get($binding, 'responsibleEmployeeId'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function limitString(?string $value, int $limit): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $limit);
    }
}
