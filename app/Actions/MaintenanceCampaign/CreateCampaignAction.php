<?php

namespace App\Actions\MaintenanceCampaign;

use App\DTOs\MaintenanceCampaign\CreateMaintenanceCampaignDTO;
use App\Models\MaintenanceCampaign;
use Illuminate\Support\Facades\DB;

class CreateCampaignAction
{
    public function execute(CreateMaintenanceCampaignDTO $dto): MaintenanceCampaign
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $data['code'] = $this->generateCode();

            return MaintenanceCampaign::create($data);
        });
    }

    private function generateCode(): string
    {
        $year = now()->format('Y');
        $last = MaintenanceCampaign::whereYear('created_at', $year)->count() + 1;

        return sprintf('CAMP-%s-%04d', $year, $last);
    }
}
