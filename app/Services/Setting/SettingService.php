<?php

namespace App\Services\Setting;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingService
{
    public function getAll(?string $group = null): Collection
    {
        return Setting::byGroup($group)
            ->where('is_sensitive', false)
            ->orderBy('group_name')
            ->orderBy('key')
            ->get();
    }

    public function updateValue(Setting $setting, string $value): Setting
    {
        $setting->update(['value' => $value]);

        return $setting->fresh();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}
