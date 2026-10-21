<?php

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group_name',
        'description',
        'is_sensitive',
    ];

    protected $casts = [
        'type' => SettingType::class,
        'is_sensitive' => 'boolean',
    ];

    public function scopeByGroup(Builder $query, ?string $group): Builder
    {
        return $group ? $query->where('group_name', $group) : $query;
    }

    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            SettingType::INTEGER => (int) $this->value,
            SettingType::BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            SettingType::JSON => json_decode($this->value, true),
            default => $this->value,
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->getTypedValue() : $default;
    }
}
