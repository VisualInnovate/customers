<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use App\Models\OrganizationApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthenticateBearerToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => __('Unauthorized. Bearer Token is missing.')], 401);
        }

        $organizationApiKey = OrganizationApiKey::where('token', $token)->whereNull('deleted_at')->first();

        if (!$organizationApiKey) {
            return response()->json(['error' => __('Unauthorized. Invalid Bearer Token.')], 401);
        }

        $tz = $organizationApiKey->organization->timezone ?? config('app.timezone');
        if($organizationApiKey->expire_at && $organizationApiKey->expire_at < now($tz)) {
            return response()->json(['error' => __('Unauthorized. Bearer Token has expired.')], 401);
        }

        ++$organizationApiKey->usage; // increment usage count
        if($organizationApiKey->limit && $organizationApiKey->limit < $organizationApiKey->usage) {
            return response()->json(['error' => __('Unauthorized. Bearer Token request limit exceeded.')], 429);
        }

        $organizationApiKey->save(); // update usage count in case of success

        // Add the api token logs with the request ip address, endpoint of the request, request headers, request body
        DB::table('api_token_logs')->insert([
            'organization_api_key_id' => $organizationApiKey->id,
            'ip_address' => $request->ip(),
            'endpoint' => $request->getRequestUri(),
            'request_headers' => json_encode($request->headers->all()),
            'request_body' => json_encode($request->all()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attach organization to the request
        $request->merge(['organization' => $organizationApiKey->organization_id]);

        return $next($request);
    }
}