<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    protected $fillable = [
        'document_type',
        'reference_type',
        'reference_id',
        'code',
        'title',
        'file_path',
        'generated_by',
        'generated_at',
        'meta_json',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'generated_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeByType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('document_type', $type) : $query;
    }
}
