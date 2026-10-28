<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsCacheService
{
    /**
     * Cache key for all settings
     */
    const CACHE_KEY = 'app_settings_all';

    /**
     * Cache TTL in seconds (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Get all settings as an array (key => value)
     *
     * @return array
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get a specific setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $settings = self::all();
        return $settings[$key] ?? $default;
    }

    /**
     * Get multiple settings by keys
     *
     * @param array $keys
     * @return array
     */
    public static function getMany(array $keys): array
    {
        $settings = self::all();
        return array_intersect_key($settings, array_flip($keys));
    }

    /**
     * Refresh the settings cache
     *
     * @return array
     */
    public static function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);
        return self::all();
    }

    /**
     * Clear the settings cache
     *
     * @return void
     */
    public static function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Update a setting and refresh cache
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        self::refresh();
    }

    /**
     * Update multiple settings and refresh cache once
     *
     * @param array $settings
     * @return void
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        self::refresh();
    }

    /**
     * Check if a setting exists
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        $settings = self::all();
        return array_key_exists($key, $settings);
    }
}
