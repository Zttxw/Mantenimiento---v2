<?php

namespace App\Models;

use App\Enums\CampaignAssetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAsset extends Model
{
    protected $fillable = [
        'campaign_id',
        'asset_id',
        'assigned_technician_id',
        'scheduled_date',
        'attended_date',
        'status',
        'attended_type',
        'attended_by_employee_id',
        'checklist_tasks',
        'asset_condition_after',
        'maintenance_case_id',
        'notes',
    ];

    protected $casts = [
        'status' => CampaignAssetStatus::class,
        'scheduled_date' => 'date',
        'attended_date' => 'date',
        'checklist_tasks' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCampaign::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_technician_id');
    }

    public function attendedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'attended_by_employee_id');
    }

    public function maintenanceCase(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCase::class);
    }
}
