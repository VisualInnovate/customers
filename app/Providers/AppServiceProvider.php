<?php

namespace App\Providers;

use App\Models\CustomTicket;
use App\Observers\CustomTicketObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!\App::environment('local')) {
            \URL::forceScheme('https');
        }

        Inertia::share([
            'auth' => function () {
                $user = Auth::user();
                return [
                    'user' => $user ? $user->only('id', 'name', 'email') : null,
                    'team' => $user && $user?->teams?->count() > 0 ? $user->teams->first() : null,
                ];
            },
        ]);

        CustomTicket::observe(CustomTicketObserver::class);
    }
}
