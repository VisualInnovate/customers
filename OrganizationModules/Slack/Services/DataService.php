<?php

namespace OrganizationModules\Slack\Services;

use App\Models\Contact;
use OrganizationModules\Shopify\App\Resources\OrganizationIntegrationResource;
use App\Models\OrganizationIntegration;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Str;
use OrganizationModules\Shopify\App\Models\ShopifyWebhookLog;

class DataService
{

    public function storeSlackyData($organizationId, $request)
    {
        $request->validate([
            'metadata' => 'required|array',
            'metadata.name' => 'required|in:slack_message,slack_channel|string|max:255',
            'metadata.active' => 'required|boolean',
            'metadata.channel_name' => 'required|string',
            'metadata.slack_webhook' => 'required|string',
        ]);

        $metadata = $request->input('metadata'); // name, active, channel_name, slack_webhook
        $integration = OrganizationIntegration::where('organization_id', $organizationId)
            ->where('name', $metadata['name'])
            ->first();
    
        if ($integration) {
            $integration->update([
                'metadata' => json_encode($request->input('metadata')),
                'active' => $metadata['active'] ?? null,
            ]);
        } else {
            $integration = OrganizationIntegration::create([
                'organization_id' => $organizationId,
                'name' => $metadata['name'] ?? null,
                'metadata' => json_encode($metadata),
                'active' => $metadata['active'] ?? null,
            ]);
        }
    }


    public function proccessSlackyData($organizationId, $request)
    {
        
    }
    
}