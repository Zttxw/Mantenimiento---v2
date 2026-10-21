<?php

namespace App\Models;

use App\Enums\MaintenanceItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceItem extends Model
{
    protected $fillable = [
        'maintenance_case_id',
        'item_type',
        'name',
        'description',
        'quantity',
        'unit_cost',
        'total_cost',
        'data_json',
    ];

    protected $casts = [
        'item_type' => MaintenanceItemType::class,
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'data_json' => 'array',
    ];

    public function maintenanceCase(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCase::class);
    }
}
