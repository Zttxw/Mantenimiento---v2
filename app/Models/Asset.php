<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'internal_code',
        'patrimonial_code',
        'name',
        'asset_type',
        'brand',
        'model',
        'serial_number',
        'status',
        'condition',
        'organizational_unit_id',
        'responsible_employee_id',
        'purchase_date',
        'reference_value',
        'specs_json',
        'extra_json',
        'notes',
    ];

    protected $casts = [
        'asset_type' => AssetType::class,
        'status' => AssetStatus::class,
        'condition' => AssetCondition::class,
        'purchase_date' => 'date',
        'reference_value' => 'decimal:2',
        'specs_json' => 'array',
        'extra_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $asset) {
            if (empty($asset->uuid)) {
                $asset->uuid = (string) Str::uuid();
            }
        });
    }

    // Relations

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class)->latest('movement_date');
    }

    public function maintenanceCases(): HasMany
    {
        return $this->hasMany(MaintenanceCase::class)->latest();
    }

    public function campaignAssets(): HasMany
    {
        return $this->hasMany(CampaignAsset::class);
    }

    public function agentDevice(): HasOne
    {
        return $this->hasOne(AgentDevice::class);
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
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('internal_code', 'like', "%{$term}%")
                ->orWhere('patrimonial_code', 'like', "%{$term}%")
                ->orWhere('serial_number', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%");
        });
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('asset_type', $type) : $query;
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeByUnit(Builder $query, ?int $unitId): Builder
    {
        return $unitId ? $query->where('organizational_unit_id', $unitId) : $query;
    }
}
