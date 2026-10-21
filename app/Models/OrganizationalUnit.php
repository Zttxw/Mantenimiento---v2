<?php

namespace App\Models;

use App\Enums\OrgUnitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalUnit extends Model
{
    protected $fillable = [
        'parent_id',
        'type',
        'code',
        'name',
        'responsible_employee_id',
        'meta_json',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'type' => OrgUnitType::class,
        'meta_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function empoyees(): HasMany
    {
        return $this->hasMany(Employee::class, 'organizational_unit_id');
    }

    public function employees(): HasMany
    {
        return $this->empoyees();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'organizational_unit_id');
    }

    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $current = $this;
        while ($current->parent) {
            $current = $current->parent;
            $path->prepend($current->name);
        }

        return $path->implode(' > ');
    }
}
