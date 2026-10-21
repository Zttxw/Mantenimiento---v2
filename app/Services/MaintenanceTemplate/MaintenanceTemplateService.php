<?php

namespace App\Services\MaintenanceTemplate;

use App\Models\MaintenanceTemplate;
use App\Models\MaintenanceTemplateItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MaintenanceTemplateService
{
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return MaintenanceTemplate::with('items')
            ->byType($filters['type'] ?? null)
            ->when(isset($filters['active']), fn ($q) => $q->active())
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function getActiveByType(?string $type): Collection
    {
        return MaintenanceTemplate::active()
            ->byType($type)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data, int $userId): MaintenanceTemplate
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $template = MaintenanceTemplate::create(array_merge($data, ['created_by' => $userId]));

        $this->syncItems($template, $items);

        return $template->load('items');
    }

    public function update(MaintenanceTemplate $template, array $data): MaintenanceTemplate
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $template->update($data);

        $this->syncItems($template, $items);

        return $template->load('items');
    }

    public function delete(MaintenanceTemplate $template): void
    {
        $template->delete();
    }

    private function syncItems(MaintenanceTemplate $template, array $items): void
    {
        $template->items()->delete();

        foreach ($items as $order => $item) {
            if (empty($item['name'])) {
                continue;
            }
            MaintenanceTemplateItem::create([
                'maintenance_template_id' => $template->id,
                'item_type' => $item['item_type'],
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit_cost' => $item['unit_cost'] ?? 0,
                'sort_order' => $order,
            ]);
        }
    }
}
