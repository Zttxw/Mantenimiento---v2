<?php

namespace App\Actions\MaintenanceCase;

use App\DTOs\MaintenanceCase\CreateMaintenanceCaseDTO;
use App\Models\MaintenanceCase;
use Illuminate\Support\Facades\DB;

class CreateCaseAction
{
    public function execute(CreateMaintenanceCaseDTO $dto): MaintenanceCase
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $data['code'] = $this->generateCode();

            return MaintenanceCase::create($data);
        });
    }

    private function generateCode(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $last = MaintenanceCase::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return sprintf('CASO-%s%s-%04d', $year, $month, $last);
    }
}
