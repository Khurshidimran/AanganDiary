<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    private const CACHE_KEY = 'app_settings';

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()->get($key, $default);
    }

    public function set(string $key, mixed $value, string $group = 'general'): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group],
        );

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    public function all(): \Illuminate\Support\Collection
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::all()->pluck('value', 'key'),
        );
    }

    public function group(string $group): \Illuminate\Support\Collection
    {
        return Setting::where('group', $group)->get()->pluck('value', 'key');
    }
}
