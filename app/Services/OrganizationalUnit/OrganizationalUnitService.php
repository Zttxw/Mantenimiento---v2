<?php

namespace App\Services\OrganizationalUnit;

use App\DTOs\OrganizationalUnitDTO;
use App\Exceptions\OrganizationalUnitException;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrganizationalUnitService
{
    public function getAll(): Collection
    {
        return OrganizationalUnit::with('parent')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return OrganizationalUnit::with('parent', 'responsible')
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
            )
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t)
            )
            ->when(isset($filters['status']), fn ($q) => $q->where('is_active', $filters['status'] === 'active')
            )
            ->orderBy('sort_order')
            ->paginate(15)
            ->withQueryString();
    }

    public function findOrFail(int $id): OrganizationalUnit
    {
        return OrganizationalUnit::findOr($id, fn () => throw OrganizationalUnitException::notFound($id));
    }

    public function create(OrganizationalUnitDTO $dto): OrganizationalUnit
    {
        return OrganizationalUnit::create($dto->toArray());
    }

    public function update(OrganizationalUnit $unit, OrganizationalUnitDTO $dto): OrganizationalUnit
    {
        if ($dto->parent_id && $this->wouldCreateCircularReference($unit, $dto->parent_id)) {
            throw OrganizationalUnitException::circularReference($unit->name);
        }

        $unit->update($dto->toArray());

        return $unit->fresh();
    }

    public function delete(OrganizationalUnit $unit): void
    {
        if ($unit->children()->exists()) {
            throw OrganizationalUnitException::hasChildren($unit->name);
        }

        if ($unit->empoyees()->exists()) {
            throw OrganizationalUnitException::hasEmployees($unit->name);
        }

        if ($unit->assets()->exists()) {
            throw OrganizationalUnitException::hasAssets($unit->name);
        }

        $unit->delete();
    }

    private function wouldCreateCircularReference(OrganizationalUnit $unit, int $newParentId): bool
    {
        $current = OrganizationalUnit::find($newParentId);

        while ($current) {
            if ($current->id === $unit->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }
}
