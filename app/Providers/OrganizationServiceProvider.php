<?php

namespace App\Providers;

use App\Models\Organization;
use Illuminate\Support\ServiceProvider;

class OrganizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Organization as a singleton that gets resolved once per request
        $this->app->singleton('current.organization', function ($app) {
            $organizationId = session('current_organization');

            if (!$organizationId) {
                return null;
            }

            // Load the organization with commonly needed relationships to reduce queries
            return Organization::with([
                // Add any commonly loaded relationships here
                // 'teams', 'subscription', etc.
            ])->find($organizationId);
        });

        // Create an alias for easier access
        $this->app->alias('current.organization', Organization::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
