<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceCampaign extends Model
{
    protected $fillable = [
        'code',
        'name',
        'objective',
        'scope_json',
        'start_date',
        'end_date',
        'status',
        'coordinator_employee_id',
        'summary',
        'metrics_json',
        'created_by',
    ];

    protected $casts = [
        'status' => CampaignStatus::class,
        'scope_json' => 'array',
        'metrics_json' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'coordinator_employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaignAssets(): HasMany
    {
        return $this->hasMany(CampaignAsset::class, 'campaign_id');
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'campaign_assets')
            ->withPivot('assigned_technician_id', 'scheduled_date', 'attended_date', 'status', 'maintenance_case_id', 'notes')
            ->withTimestamps();
    }

    public function maintenanceCases(): HasMany
    {
        return $this->hasMany(MaintenanceCase::class, 'campaign_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%");
        });
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }
}
