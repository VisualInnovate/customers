<?php

namespace OrganizationModules\LiveChat\App\Http\Middleware;

use App\Models\OrganizationIntegration;
use Closure;
use Illuminate\Http\Request;

class DomainCheckMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        $apiKey = $request->query('chat_key');
        
        $integration = OrganizationIntegration::where('name', 'LiveChat')
            ->where('metadata->api_key', $apiKey)
            ->first();
    
        if (!$integration) {
            abort(403, 'Invalid chat integration');
        }
        $allowedDomain = $integration->metadata['domain'] ?? null;
        
        $originHost = parse_url($request->header('origin'), PHP_URL_HOST);
        $requestDomain = parse_url($request->domain, PHP_URL_HOST) ?? $originHost;
    
        if ($requestDomain !== $allowedDomain) {
            abort(403, 'Unauthorized domain: '.$requestDomain);
        }
    
        return $next($request);
    }
}
