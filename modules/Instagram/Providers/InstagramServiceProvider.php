<?php

namespace Modules\Instagram\Providers;

use Illuminate\Support\ServiceProvider;


class InstagramServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->register(InstagramRouteServiceProvider::class);
    }
}   