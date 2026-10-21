<?php

namespace App\Models;

use App\Enums\MaintenanceItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTemplateItem extends Model
{
    protected $fillable = [
        'maintenance_template_id',
        'item_type',
        'name',
        'description',
        'quantity',
        'unit_cost',
        'sort_order',
    ];

    protected $casts = [
        'item_type' => MaintenanceItemType::class,
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTemplate::class, 'maintenance_template_id');
    }
}
