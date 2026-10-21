<?php

namespace App\Actions\MaintenanceCase;

use App\DTOs\MaintenanceCase\UpdateMaintenanceCaseDTO;
use App\Enums\MaintenanceCaseStatus;
use App\Exceptions\MaintenanceCase\MaintenanceCaseException;
use App\Models\MaintenanceCase;
use Illuminate\Support\Facades\DB;

class UpdateCaseAction
{
    public function execute(MaintenanceCase $case, UpdateMaintenanceCaseDTO $dto): MaintenanceCase
    {
        if ($case->status === MaintenanceCaseStatus::COMPLETADO || $case->status === MaintenanceCaseStatus::CANCELADO) {
            throw MaintenanceCaseException::alreadyClosed($case->code);
        }

        return DB::transaction(function () use ($case, $dto) {
            $case->update($dto->toArray());

            return $case->fresh();
        });
    }
}
