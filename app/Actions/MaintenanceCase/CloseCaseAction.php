<?php

namespace App\Actions\MaintenanceCase;

use App\Enums\MaintenanceCaseStatus;
use App\Exceptions\MaintenanceCase\MaintenanceCaseException;
use App\Models\MaintenanceCase;
use Illuminate\Support\Facades\DB;

class CloseCaseAction
{
    public function execute(MaintenanceCase $case, array $data): MaintenanceCase
    {
        if ($case->status === MaintenanceCaseStatus::COMPLETADO || $case->status === MaintenanceCaseStatus::CANCELADO) {
            throw MaintenanceCaseException::alreadyClosed($case->code);
        }

        return DB::transaction(function () use ($case, $data) {
            $case->update([
                'status' => MaintenanceCaseStatus::COMPLETADO->value,
                'finished_at' => $data['finished_at'] ?? now(),
                'actions_taken' => $data['actions_taken'] ?? $case->actions_taken,
                'conformity_name' => $data['conformity_name'] ?? null,
                'conformity_date' => $data['conformity_date'] ?? now()->toDateString(),
                'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
                'total_cost' => $data['total_cost'] ?? $case->items()->sum('total_cost'),
                'notes' => $data['notes'] ?? $case->notes,
            ]);

            return $case->fresh();
        });
    }
}
