<?php

namespace App\Models;

use App\Enums\MaintenanceCaseStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCase extends Model
{
    protected $fillable = [
        'code',
        'asset_id',
        'campaign_id',
        'reported_by_employee_id',
        'assigned_technician_id',
        'maintenance_type',
        'priority',
        'status',
        'problem_description',
        'diagnosis',
        'actions_taken',
        'started_at',
        'finished_at',
        'next_maintenance_date',
        'conformity_name',
        'conformity_date',
        'total_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'maintenance_type' => MaintenanceType::class,
        'priority' => MaintenancePriority::class,
        'status' => MaintenanceCaseStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'next_maintenance_date' => 'date',
        'conformity_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCampaign::class, 'campaign_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reported_by_employee_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_technician_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaintenanceItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'reference_id')
            ->where('reference_type', self::class);
    }

    // Scopes

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
                ->orWhere('problem_description', 'like', "%{$term}%")
                ->orWhereHas('asset', fn ($a) => $a->where('name', 'like', "%{$term}%")
                    ->orWhere('internal_code', 'like', "%{$term}%"));
        });
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('maintenance_type', $type) : $query;
    }

    public function scopeByTechnician(Builder $query, ?int $technicianId): Builder
    {
        return $technicianId ? $query->where('assigned_technician_id', $technicianId) : $query;
    }
}
