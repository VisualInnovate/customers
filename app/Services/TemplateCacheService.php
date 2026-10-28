<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Support\Facades\Cache;

class TemplateCacheService
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    const CACHE_TTL = 1800;

    /**
     * Get cache key for organization templates
     *
     * @param int $organizationId
     * @return string
     */
    private static function getCacheKey(int $organizationId): string
    {
        return "templates_org_{$organizationId}";
    }

    /**
     * Get all approved templates for an organization
     *
     * @param int $organizationId
     * @return \Illuminate\Support\Collection
     */
    public static function getApproved(int $organizationId)
    {
        return Cache::remember(
            self::getCacheKey($organizationId),
            self::CACHE_TTL,
            function () use ($organizationId) {
                return Template::where('organization_id', $organizationId)
                    ->whereNull('deleted_at')
                    ->where('status', 'APPROVED')
                    ->get();
            }
        );
    }

    /**
     * Get all templates for an organization (including non-approved)
     *
     * @param int $organizationId
     * @return \Illuminate\Support\Collection
     */
    public static function getAll(int $organizationId)
    {
        $cacheKey = "templates_all_org_{$organizationId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organizationId) {
            return Template::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->get();
        });
    }

    /**
     * Get a single template by UUID (with caching)
     *
     * @param string $uuid
     * @return Template|null
     */
    public static function getByUuid(string $uuid)
    {
        $cacheKey = "template_uuid_{$uuid}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($uuid) {
            return Template::where('uuid', $uuid)
                ->whereNull('deleted_at')
                ->first();
        });
    }

    /**
     * Get count of templates for an organization
     *
     * @param int $organizationId
     * @return int
     */
    public static function count(int $organizationId): int
    {
        $cacheKey = "templates_count_org_{$organizationId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organizationId) {
            return Template::where('organization_id', $organizationId)
                ->whereNull('deleted_at')
                ->count();
        });
    }

    /**
     * Refresh cache for a specific organization
     *
     * @param int $organizationId
     * @return void
     */
    public static function refresh(int $organizationId): void
    {
        Cache::forget(self::getCacheKey($organizationId));
        Cache::forget("templates_all_org_{$organizationId}");
        Cache::forget("templates_count_org_{$organizationId}");

        // Pre-warm the cache
        self::getApproved($organizationId);
    }

    /**
     * Clear cache for a specific template UUID
     *
     * @param string $uuid
     * @return void
     */
    public static function clearByUuid(string $uuid): void
    {
        Cache::forget("template_uuid_{$uuid}");
    }

    /**
     * Clear all template caches for an organization
     *
     * @param int $organizationId
     * @return void
     */
    public static function clear(int $organizationId): void
    {
        Cache::forget(self::getCacheKey($organizationId));
        Cache::forget("templates_all_org_{$organizationId}");
        Cache::forget("templates_count_org_{$organizationId}");
    }

    /**
     * Clear all template caches (all organizations)
     * Useful after bulk operations
     *
     * @return void
     */
    public static function clearAll(): void
    {
        // This clears by pattern - requires Redis or similar cache driver
        // For file/database cache, this won't work perfectly
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Cache::getStore()->connection();
            $keys = $redis->keys('*templates_*');
            if (!empty($keys)) {
                $redis->del($keys);
            }
        } else {
            // Fallback: just flush the entire cache (use carefully)
            // Cache::flush();
        }
    }

    /**
     * Invalidate cache when template is updated
     *
     * @param Template $template
     * @return void
     */
    public static function invalidate(Template $template): void
    {
        if ($template->organization_id) {
            self::clear($template->organization_id);
        }

        if ($template->uuid) {
            self::clearByUuid($template->uuid);
        }
    }
}
