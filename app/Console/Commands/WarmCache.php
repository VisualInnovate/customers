<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\Template;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm 
                            {--organization= : Warm cache for specific organization}
                            {--all : Warm cache for all organizations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Warming up cache...');
        $this->newLine();

        // Cache global settings
        $this->info('Caching global settings...');
        $this->cacheGlobalSettings();

        // Cache Pusher settings
        $this->info('Caching Pusher settings...');
        $this->cachePusherSettings();

        if ($this->option('all')) {
            $this->info('Caching all organizations...');
            $organizations = Organization::all();

            $bar = $this->output->createProgressBar(count($organizations));
            $bar->start();

            foreach ($organizations as $organization) {
                $this->cacheOrganization($organization);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        } elseif ($this->option('organization')) {
            $orgId = $this->option('organization');
            $organization = Organization::find($orgId);

            if ($organization) {
                $this->info("Caching organization {$orgId}...");
                $this->cacheOrganization($organization);
            } else {
                $this->error("Organization {$orgId} not found.");
                return 1;
            }
        }

        $this->newLine();
        $this->info('✓ Cache warmed successfully!');

        return 0;
    }

    /**
     * Cache global settings
     */
    private function cacheGlobalSettings()
    {
        $keys = ['favicon', 'logo', 'company_name', 'address', 'currency', 'email', 'phone',
                 'socials', 'trial_period', 'recaptcha_site_key', 'recaptcha_active',
                 'google_analytics_tracking_id', 'google_maps_api_key', 'pusher_app_key',
                 'pusher_app_cluster', 'google_auth_active', 'enable_api_key_input',
                 'enable_model_selection', 'default_open_ai_text_model', 'default_open_ai_audio_model'];

        Cache::remember("settings_cache", 7200, function () use ($keys) {
            return Setting::whereIn('key', $keys)->get();
        });
    }

    /**
     * Cache Pusher settings
     */
    private function cachePusherSettings()
    {
        Cache::remember('pusher_settings', 7200, function() {
            return Setting::whereIn('key', [
                'pusher_app_id',
                'pusher_app_key',
                'pusher_app_secret',
                'pusher_app_cluster',
            ])->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Cache organization-specific data
     */
    private function cacheOrganization(Organization $organization)
    {
        $orgId = $organization->id;

        // Cache organization config
        Cache::remember("org_config_{$orgId}", 3600, function() use ($organization) {
            return $organization->metadata ? json_decode($organization->metadata, true) : [];
        });

        // Cache templates
        Cache::remember("templates_org_{$orgId}", 1800, function() use ($orgId) {
            return Template::where('organization_id', $orgId)
                ->where('deleted_at', null)
                ->where('status', 'APPROVED')
                ->get();
        });

        // Cache dashboard counts
        Cache::remember("dashboard_counts_{$orgId}", 300, function() use ($orgId) {
            return [
                'chatCount' => \App\Models\Chat::where('organization_id', $orgId)
                    ->whereNull('deleted_at')
                    ->whereHas('contact', function ($query) {
                        $query->whereNull('deleted_at');
                    })
                    ->count(),
                'campaignCount' => \App\Models\Campaign::where('organization_id', $orgId)
                    ->whereNull('deleted_at')
                    ->count(),
                'contactCount' => \App\Models\Contact::where('organization_id', $orgId)
                    ->whereNull('deleted_at')
                    ->count(),
                'templateCount' => Template::where('organization_id', $orgId)
                    ->whereNull('deleted_at')
                    ->count(),
            ];
        });
    }
}

