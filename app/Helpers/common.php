<?php

// global function that retrieves the current organization ID from the session
use App\Models\Organization;
use App\Services\WhatsappService;
use Propaganistas\LaravelPhone\PhoneNumber;

if (!function_exists('organization_id')) {
    function organization_id(): ?int {
        return session('current_organization', null);
    }
}

if (!function_exists('current_organization')) {
    /**
     * Get the current organization singleton instance.
     * This is loaded once per request and reused across all services.
     *
     * @return Organization|null
     */
    function current_organization(): ?Organization {
        return app('current.organization');
    }
}

if(!function_exists('sanitize_phone')) {
    function sanitize_phone($phone): PhoneNumber
    {
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }
        return new PhoneNumber($phone);
    }
}

if(!function_exists('whatsapp_service')) {
    function whatsapp_service(): ?WhatsappService
    {
        $organization = current_organization();
        if(!$organization) {
            return null;
        }

        $metadata = json_decode($organization->metadata, true)['whatsapp'];

        return new WhatsappService(
            $metadata['access_token'],
            config('graph.api_version'),
            $metadata['app_id'],
            $metadata['phone_number_id'],
            $metadata['waba_id'],
            $organization->id
        );
    }
}

if (!function_exists('setting')) {
    /**
     * Get a setting value from cache
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function setting(?string $key = null, $default = null)
    {
        if ($key === null) {
            return \App\Services\SettingsCacheService::all();
        }

        return \App\Services\SettingsCacheService::get($key, $default);
    }
}

if (!function_exists('settings')) {
    /**
     * Get multiple settings by keys
     *
     * @param array $keys
     * @return array
     */
    function settings(array $keys): array
    {
        return \App\Services\SettingsCacheService::getMany($keys);
    }
}

if (!function_exists('setting_refresh')) {
    /**
     * Refresh the settings cache
     *
     * @return array
     */
    function setting_refresh(): array
    {
        return \App\Services\SettingsCacheService::refresh();
    }
}

if (!function_exists('templates')) {
    /**
     * Get cached templates for current organization
     *
     * @param int|null $organizationId
     * @param bool $approvedOnly
     * @return \Illuminate\Support\Collection
     */
    function templates(?int $organizationId = null, bool $approvedOnly = true)
    {
        $orgId = $organizationId ?? organization_id();

        if (!$orgId) {
            return collect([]);
        }

        return $approvedOnly
            ? \App\Services\TemplateCacheService::getApproved($orgId)
            : \App\Services\TemplateCacheService::getAll($orgId);
    }
}

if (!function_exists('template_by_uuid')) {
    /**
     * Get a single template by UUID from cache
     *
     * @param string $uuid
     * @return \App\Models\Template|null
     */
    function template_by_uuid(string $uuid)
    {
        return \App\Services\TemplateCacheService::getByUuid($uuid);
    }
}

