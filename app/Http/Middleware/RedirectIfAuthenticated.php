<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $frontend = Setting::where('key', 'display_frontend')->first();
        $frontend_active = $frontend ? $frontend->value : 1;

        if (!$frontend_active) {
            $guards = empty($guards) ? ['user', 'admin'] : $guards;

            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    if ($user->role == 'admin') {
                        return redirect('/admin/dashboard');
                    } else {
                        return redirect('/dashboard');
                    }
                }
            }
        }

        return $next($request);
    }
}
