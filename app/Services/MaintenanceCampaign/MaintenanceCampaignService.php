<?php

namespace App\Services\MaintenanceCampaign;

use App\Actions\MaintenanceCampaign\CreateCampaignAction;
use App\Actions\MaintenanceCampaign\UpdateCampaignAction;
use App\DTOs\MaintenanceCampaign\CreateMaintenanceCampaignDTO;
use App\DTOs\MaintenanceCampaign\UpdateMaintenanceCampaignDTO;
use App\Enums\CampaignAssetStatus;
use App\Enums\CampaignStatus;
use App\Exceptions\Asset\AssetException;
use App\Models\Asset;
use App\Models\CampaignAsset;
use App\Models\MaintenanceCampaign;
use App\Models\User;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class MaintenanceCampaignService
{
    public function __construct(
        private readonly CreateCampaignAction $createAction,
        private readonly UpdateCampaignAction $updateAction,
    ) {}

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return MaintenanceCampaign::with('coordinator')
            ->search($filters['search'] ?? null)
            ->byStatus($filters['status'] ?? null)
            ->latest('start_date')
            ->paginate(10)
            ->withQueryString();
    }

    public function create(CreateMaintenanceCampaignDTO $dto): MaintenanceCampaign
    {
        return $this->createAction->execute($dto);
    }

    public function update(MaintenanceCampaign $campaign, UpdateMaintenanceCampaignDTO $dto): MaintenanceCampaign
    {
        return $this->updateAction->execute($campaign, $dto);
    }

    public function changeStatus(MaintenanceCampaign $campaign, CampaignStatus $status): MaintenanceCampaign
    {
        $campaign->update(['status' => $status->value]);

        return $campaign->fresh();
    }

    public function addAsset(MaintenanceCampaign $campaign, int $assetId, array $data = []): CampaignAsset
    {
        if ($campaign->campaignAssets()->where('asset_id', $assetId)->exists()) {
            throw AssetException::alreadyAssigned((string) $assetId);
        }

        return $campaign->campaignAssets()->create([
            'asset_id' => $assetId,
            'assigned_technician_id' => $data['assigned_technician_id'] ?? null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'status' => CampaignAssetStatus::PENDIENTE->value,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function removeAsset(MaintenanceCampaign $campaign, int $assetId): void
    {
        $campaign->campaignAssets()->where('asset_id', $assetId)->delete();
    }

    public function bulkAddByUnit(MaintenanceCampaign $campaign, int $unitId, array $data = []): int
    {
        $existingIds = $campaign->campaignAssets()->pluck('asset_id');
        $assets = Asset::where('organizational_unit_id', $unitId)
            ->whereNotIn('id', $existingIds)
            ->get();

        foreach ($assets as $asset) {
            $campaign->campaignAssets()->create([
                'asset_id' => $asset->id,
                'assigned_technician_id' => $data['assigned_technician_id'] ?: null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'status' => CampaignAssetStatus::PENDIENTE->value,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return $assets->count();
    }

    /**
     * Registra manualmente la atención preventiva de un activo durante una campaña activa.
     * Actualiza la condición y datos de ubicación/custodio del activo si fueron provistos en campo.
     */
    public function attendAssetManually(
        MaintenanceCampaign $campaign,
        CampaignAsset $campaignAsset,
        array $data,
        ?User $user = null
    ): CampaignAsset {
        if ($campaign->status !== CampaignStatus::EN_CURSO) {
            throw new DomainException('Solo se pueden registrar atenciones preventivas cuando la campaña esté activa (en curso).');
        }

        $asset = $campaignAsset->asset;

        // Si se actualizaron datos físicos en caliente (código patrimonial, oficina, responsable, etc.)
        $assetUpdates = [];
        if (! empty($data['patrimonial_code'])) {
            $assetUpdates['patrimonial_code'] = $data['patrimonial_code'];
        }
        if (! empty($data['organizational_unit_id'])) {
            $assetUpdates['organizational_unit_id'] = (int) $data['organizational_unit_id'];
        }
        if (! empty($data['responsible_employee_id'])) {
            $assetUpdates['responsible_employee_id'] = (int) $data['responsible_employee_id'];
        }
        if (! empty($data['asset_condition_after'])) {
            $assetUpdates['condition'] = $data['asset_condition_after'];
        }
        if (! empty($assetUpdates)) {
            $asset->update($assetUpdates);
        }

        $technicianId = ! empty($data['attended_by_employee_id'])
            ? (int) $data['attended_by_employee_id']
            : ($user?->employee?->id ?? $campaignAsset->assigned_technician_id);

        $campaignAsset->update([
            'status' => CampaignAssetStatus::ATENDIDO,
            'attended_type' => 'manual',
            'attended_date' => $data['attended_date'] ?? now()->toDateString(),
            'attended_by_employee_id' => $technicianId,
            'checklist_tasks' => $data['checklist_tasks'] ?? [],
            'asset_condition_after' => $data['asset_condition_after'] ?? ($asset->condition?->value ?? 'bueno'),
            'notes' => $data['notes'] ?? $campaignAsset->notes,
        ]);

        return $campaignAsset->fresh();
    }

    /**
     * Registro rápido en campo de activos descubiertos (monitores, pantallas, impresoras) y enlace directo a campaña.
     */
    public function quickRegisterAsset(
        MaintenanceCampaign $campaign,
        array $assetData,
        array $campaignData = [],
        ?User $user = null
    ): CampaignAsset {
        $prefix = match (strtolower($assetData['asset_type'] ?? '')) {
            'impresora' => 'IMP',
            'monitor' => 'MON',
            'ups' => 'UPS',
            'scanner' => 'SCN',
            'switch', 'router' => 'RED',
            default => 'EQ',
        };

        $internalCode = ! empty($assetData['internal_code'])
            ? $assetData['internal_code']
            : $prefix.'-'.strtoupper(Str::random(6));

        $asset = Asset::create([
            'uuid' => (string) Str::uuid(),
            'internal_code' => $internalCode,
            'patrimonial_code' => $assetData['patrimonial_code'] ?? null,
            'name' => $assetData['name'],
            'asset_type' => $assetData['asset_type'],
            'brand' => $assetData['brand'] ?? null,
            'model' => $assetData['model'] ?? null,
            'serial_number' => $assetData['serial_number'] ?? null,
            'status' => 'en_uso',
            'condition' => $assetData['condition'] ?? 'bueno',
            'organizational_unit_id' => $assetData['organizational_unit_id'] ?? null,
            'responsible_employee_id' => $assetData['responsible_employee_id'] ?? null,
            'notes' => $assetData['notes'] ?? null,
        ]);

        $campaignAsset = $this->addAsset($campaign, $asset->id, [
            'assigned_technician_id' => $campaignData['assigned_technician_id'] ?? null,
            'scheduled_date' => $campaignData['scheduled_date'] ?? now()->toDateString(),
            'notes' => $campaignData['notes'] ?? null,
        ]);

        if (! empty($campaignData['mark_attended']) && $campaign->status === CampaignStatus::EN_CURSO) {
            $this->attendAssetManually($campaign, $campaignAsset, $campaignData, $user);
        }

        return $campaignAsset->fresh();
    }

    public function delete(MaintenanceCampaign $campaign): void
    {
        $campaign->delete();
    }
}
