<?php

namespace App\Actions\Asset;

use App\DTOs\Asset\UpdateAssetDTO;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class UpdateAssetAction
{
    public function execute(Asset $asset, UpdateAssetDTO $dto): Asset
    {
        return DB::transaction(function () use ($asset, $dto) {
            $asset->update($dto->toArray());

            return $asset->fresh();
        });
    }
}
