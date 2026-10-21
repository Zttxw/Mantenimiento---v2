<?php

namespace App\Models;

use App\Enums\AiLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiLog extends Model
{
    protected $fillable = [
        'user_id',
        'context_type',
        'context_id',
        'message',
        'response',
        'status',
        'applied_action',
        'meta_json',
    ];

    protected $casts = [
        'status' => AiLogStatus::class,
        'meta_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function context(): MorphTo
    {
        return $this->morphTo('context');
    }
}
