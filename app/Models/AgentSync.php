<?php

namespace App\Models;

use App\Enums\AgentSyncStatus;
use App\Enums\AgentSyncType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSync extends Model
{
    protected $fillable = [
        'agent_device_id',
        'sync_type',
        'payload_json',
        'detected_changes_json',
        'status',
        'synced_at',
    ];

    protected $casts = [
        'sync_type' => AgentSyncType::class,
        'status' => AgentSyncStatus::class,
        'payload_json' => 'array',
        'detected_changes_json' => 'array',
        'synced_at' => 'datetime',
    ];

    public function agentDevice(): BelongsTo
    {
        return $this->belongsTo(AgentDevice::class);
    }
}
