<?php

namespace Modules\Shopify\Providers;

use Illuminate\Support\ServiceProvider;


class ShopifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}