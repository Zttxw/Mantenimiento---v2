<?php

namespace App\Models;

use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTemplate extends Model
{
    protected $fillable = [
        'name',
        'maintenance_type',
        'asset_type',
        'description',
        'problem_description',
        'diagnosis_template',
        'actions_template',
        'steps_json',
        'next_maintenance_interval_days',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'maintenance_type' => MaintenanceType::class,
        'steps_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MaintenanceTemplateItem::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('maintenance_type', $type) : $query;
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'maintenance_type' => $this->maintenance_type?->value,
            'problem_description' => $this->problem_description,
            'diagnosis_template' => $this->diagnosis_template,
            'actions_template' => $this->actions_template,
            'steps_json' => $this->steps_json ?? [],
            'next_maintenance_interval_days' => $this->next_maintenance_interval_days,
            'items' => $this->items->map(fn ($i) => [
                'item_type' => $i->item_type,
                'name' => $i->name,
                'description' => $i->description,
                'quantity' => $i->quantity,
                'unit_cost' => $i->unit_cost,
            ])->toArray(),
        ];
    }
}
