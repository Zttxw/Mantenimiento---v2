<?php

namespace App\Actions\MaintenanceCampaign;

use App\DTOs\MaintenanceCampaign\UpdateMaintenanceCampaignDTO;
use App\Models\MaintenanceCampaign;
use Illuminate\Support\Facades\DB;

class UpdateCampaignAction
{
    public function execute(MaintenanceCampaign $campaign, UpdateMaintenanceCampaignDTO $dto): MaintenanceCampaign
    {
        return DB::transaction(function () use ($campaign, $dto) {
            $campaign->update($dto->toArray());

            return $campaign->fresh();
        });
    }
}
