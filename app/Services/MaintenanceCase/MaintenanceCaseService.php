<?php

namespace App\Services\MaintenanceCase;

use App\Actions\MaintenanceCase\CloseCaseAction;
use App\Actions\MaintenanceCase\CreateCaseAction;
use App\Actions\MaintenanceCase\UpdateCaseAction;
use App\DTOs\MaintenanceCase\CreateMaintenanceCaseDTO;
use App\DTOs\MaintenanceCase\UpdateMaintenanceCaseDTO;
use App\DTOs\MaintenanceItem\CreateMaintenanceItemDTO;
use App\Models\MaintenanceCase;
use App\Models\MaintenanceItem;
use Illuminate\Pagination\LengthAwarePaginator;

class MaintenanceCaseService
{
    public function __construct(
        private readonly CreateCaseAction $createAction,
        private readonly UpdateCaseAction $updateAction,
        private readonly CloseCaseAction $closeAction,
    ) {}

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return MaintenanceCase::with('asset', 'assignedTechnician')
            ->whereNull('campaign_id') // Aislamiento total: solo tickets de soporte y mesa de ayuda
            ->search($filters['search'] ?? null)
            ->byStatus($filters['status'] ?? null)
            ->byType($filters['type'] ?? null)
            ->byTechnician($filters['technician_id'] ?? null)
            ->when($filters['asset_id'] ?? null, fn ($q, $id) => $q->where('asset_id', $id))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function create(CreateMaintenanceCaseDTO $dto): MaintenanceCase
    {
        return $this->createAction->execute($dto);
    }

    public function update(MaintenanceCase $case, UpdateMaintenanceCaseDTO $dto): MaintenanceCase
    {
        return $this->updateAction->execute($case, $dto);
    }

    public function close(MaintenanceCase $case, array $data): MaintenanceCase
    {
        return $this->closeAction->execute($case, $data);
    }

    public function addItem(MaintenanceCase $case, CreateMaintenanceItemDTO $dto): MaintenanceItem
    {
        return $case->items()->create($dto->toArray());
    }

    public function removeItem(MaintenanceCase $case, MaintenanceItem $item): void
    {
        abort_if($item->maintenance_case_id !== $case->id, 403);
        $item->delete();
    }

    public function delete(MaintenanceCase $case): void
    {
        $case->delete();
    }
}
