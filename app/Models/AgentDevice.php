<?php

namespace App\Models;

use App\Enums\AgentDeviceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AgentDevice extends Model
{
    protected $fillable = [
        'uuid',
        'asset_id',
        'hostname',
        'serial_number',
        'device_model',
        'operating_system',
        'agent_version',
        'last_ip',
        'last_heartbeat_at',
        'status',
        'last_snapshot_json',
        'last_health_json',
        'api_token',
    ];

    protected $casts = [
        'status' => AgentDeviceStatus::class,
        'last_heartbeat_at' => 'datetime',
        'last_snapshot_json' => 'array',
        'last_health_json' => 'array',
    ];

    protected $hidden = ['api_token'];

    protected static function booted(): void
    {
        static::creating(function (self $device) {
            if (empty($device->uuid)) {
                $device->uuid = (string) Str::uuid();
            }
            if (empty($device->api_token)) {
                $device->api_token = hash('sha256', Str::random(60));
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(AgentSync::class)->latest('synced_at');
    }

    public function lastSync(): HasMany
    {
        return $this->hasMany(AgentSync::class)->latest('synced_at')->limit(1);
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function isOnline(): bool
    {
        return $this->last_heartbeat_at
            && $this->last_heartbeat_at->diffInMinutes(now()) <= 10;
    }
}
