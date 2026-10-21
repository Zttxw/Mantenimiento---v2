<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $fillable = [
        'dni',
        'name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'position',
        'organizational_unit_id',
        'is_technician',
        'specialty',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_technician' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function assignedAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'responsible_employee_id');
    }

    public function maintenanceCases(): HasMany
    {
        return $this->hasMany(MaintenanceCase::class, 'assigned_technician_id');
    }

    public function reportedCases(): HasMany
    {
        return $this->hasMany(MaintenanceCase::class, 'reported_by_employee_id');
    }

    public function coordinatedCampaigns(): HasMany
    {
        return $this->hasMany(MaintenanceCampaign::class, 'coordinator_employee_id');
    }

    public function scopeTechnicians(Builder $query): Builder
    {
        return $query->where('is_technician', true)->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
                ->orWhere('dni', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('position', 'like', "%{$term}%");
        });
    }
}
