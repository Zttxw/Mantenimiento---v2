<?php

namespace App\Services\Asset;

use App\Actions\Asset\CreateAssetAction;
use App\Actions\Asset\UpdateAssetAction;
use App\DTOs\Asset\CreateAssetDTO;
use App\DTOs\Asset\UpdateAssetDTO;
use App\Exceptions\Asset\AssetException;
use App\Models\Asset;
use Illuminate\Pagination\LengthAwarePaginator;

class AssetService
{
    public function __construct(
        private readonly CreateAssetAction $createAction,
        private readonly UpdateAssetAction $updateAction,
    ) {}

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return Asset::with('organizationalUnit', 'responsible', 'agentDevice')
            ->search($filters['search'] ?? null)
            ->byType($filters['type'] ?? null)
            ->byStatus($filters['status'] ?? null)
            ->byUnit($filters['unit_id'] ?? null)
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function create(CreateAssetDTO $dto): Asset
    {
        return $this->createAction->execute($dto);
    }

    public function update(Asset $asset, UpdateAssetDTO $dto): Asset
    {
        return $this->updateAction->execute($asset, $dto);
    }

    public function delete(Asset $asset): void
    {
        if ($asset->maintenanceCases()
            ->whereNotIn('status', ['completado', 'cancelado'])
            ->exists()) {
            throw AssetException::hasActiveMaintenanceCases($asset->name);
        }

        $asset->delete();
    }
}
