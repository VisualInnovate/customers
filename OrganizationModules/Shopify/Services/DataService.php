<?php

namespace OrganizationModules\Shopify\Services;

use App\Models\Contact;
use OrganizationModules\Shopify\App\Resources\OrganizationIntegrationResource;
use App\Models\OrganizationIntegration;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OrganizationModules\Shopify\App\Models\ShopifyWebhookLog;

class DataService
{

    // Step 1: Generate Shopify OAuth URL
    public function getShopifyOAuthUrl($shop)
    {
        $apiKey = '9f5bb787d6bcbd1ed4f19790fb71fbdb';
        $scope = implode(',', [
            'write_orders', 'read_orders', 'read_customer_events', 'read_delivery_customizations',
            'write_delivery_customizations', 'read_draft_orders', 'write_draft_orders',
            'read_fulfillments', 'write_fulfillments', 'read_order_edits', 'write_order_edits',
            'read_shipping', 'write_shipping', 'write_products', 'read_products',
            'write_customers', 'read_customers',
        ]);
        $redirectUri = route('shopify.callback');
        $state = Str::random(40);

        return "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $apiKey,
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    // Step 2: Exchange the code for an access token
    public function getAccessToken($shop, $code)
    {
        $apiKey = '9f5bb787d6bcbd1ed4f19790fb71fbdb';
        $apiSecret = '8f5ae10434ae7858bddde185cb3f4aae';
        $redirectUri = route('shopify.callback');

        // Make a request to exchange the code for an access token
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => $apiKey,
            'client_secret' => $apiSecret,
            'code' => $code,
        ]);

        $data = $response->json();

        // Check for success and return the access token
        if (isset($data['access_token'])) {
            return $data['access_token'];
        }

        throw new \Exception('Failed to get access token from Shopify');
    }

    public function storeShopCredentials($shop, $accessToken)
    {
        ShopifyStore::create([
            'shop_domain' => $shop,
            'access_token' => $accessToken,
            'scopes' => 'read_orders,write_products', // Adjust based on your granted permissions
        ]);
    }

    public function registerWebhook($shop, $accessToken, $topic, $address)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->post("https://{$shop}/admin/api/2024-01/webhooks.json", [
            'webhook' => [
                'topic' => $topic,
                'address' => $address,
                'format' => 'json',
            ],
        ]);

        // Handle response, e.g., check for errors or return success
        if ($response->successful()) {
            return true;
        }

        return false;
    }
    
    public function getShopifyData($request, $organizationId, $id = null)
    {
        $data = OrganizationIntegration::where('organization_id', $organizationId)->where('name', $request->name)->first();
        if (!$data) {
            return response()->json([]);
        }
        return new OrganizationIntegrationResource($data);
    }


    public function storeShopifyData($organizationId, $request)
    {
        $metadata = $request->input('metadata');
        $staticScopes = 'write_orders,read_orders,read_products,write_products';
        $staticWebhookTopic = 'orders/create';
        $shopDomain = $metadata['shop_domain'];
        $accessToken = $metadata['access_token'];
        $webhookAddress = route('shopify.webhook', ['organizationId' => $organizationId]); // Webhook URL

        $client = new Client();

        $response = $client->request('GET', "https://{$shopDomain}/admin/api/2024-10/webhooks.json", [
            'headers' => [
                'X-Shopify-Access-Token' => $accessToken, // Replace with your actual access token
            ]
        ]);

        
        $webhooks = json_decode($response->getBody()->getContents(), true);
        
        // Check if the webhook already exists
        $exists = false;
        foreach ($webhooks['webhooks'] as $webhook) {
            if ($webhook['topic'] === $staticWebhookTopic) {
                $exists = true;
                $webhookData = $webhook;
                break;
            }
            else
            {
                $webhookData = $this->createShopifyWebhook($shopDomain, $accessToken, $staticWebhookTopic, $webhookAddress);
            }
        }
        
        // dd($webhookAddress);
        
        if (isset($webhookData['error']) && $webhookData['error'] === true || !$webhookData) {
            return response()->json([
                'error' => true,
                'message' => $webhookData['message'] ?? 'Failed to register webhook',
            ], 400);
        }
        
        // dd($webhookData);
        $metadata['scopes'] = $staticScopes;
        $metadata['webhook'] = $webhookData;
        $metadata['webhook_topic'] = $staticWebhookTopic;

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
    
        return new OrganizationIntegrationResource($integration);
    }


    protected function createShopifyWebhook($shopDomain, $accessToken, $topic, $address)
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->post("https://{$shopDomain}/admin/api/2024-10/webhooks.json", [
                'webhook' => [
                    'address' => $address,
                    'topic' => $topic,
                    'format' => 'json',
                ],
            ]);
                
            // dd($response);
            if ($response->status() === 201) {
                return $response->json(); // Successful webhook creation
            } elseif ($response->status() === 422) {
                return [
                    'error' => true,
                    'message' => 'Invalid request format',
                    'body' => $response->json(),
                ];
            } elseif ($response->status() === 401) {
                return [
                    'error' => true,
                    'message' => 'Unauthorized: Invalid access token',
                ];
            } else {
                return [
                    'error' => true,
                    'message' => 'Unexpected error',
                    'status_code' => $response->status(),
                    'body' => $response->json(),
                ];
            }
    
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'error' => true,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }
    
    
    public function handleShopifyWebhook($accessToken, $decodedData, $organizationId, $phone, $firstName, $lastName)
    {
        $contact = Contact::where('phone', $phone)->first();
        if (!$contact) {
            // Log::error('Contact not found for phone: ' . $phone);
            return response()->json(['error' => 'Contact not found for phone: ' . $phone], 404);
        }
        else
        {
            $contact = Contact::create([
                'first_name' => $firstName ?? 'shopify contact',
                'last_name' => $lastName ?? null,
                'email' => null,
                'phone' => $phone,
                'organization_id' => $organizationId,
                'created_by' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        ShopifyWebhookLog::create([
            'organization_id' => $organizationId,
            'payload' => json_encode($decodedData),
            'contact_id' => $contact->id
        ]);


        $apiVersion = env('GRAPH_API_VERSION', 'v20.0');
        $client = new Client();
        $url = "https://graph.facebook.com/{$apiVersion}/messages";

        $response = $client->post($url, [
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'text' => [
                    'body' => "Order Number: {$decodedData['order_number']}\n" .
                              "Name: {$decodedData['customer']['first_name']} {$decodedData['customer']['last_name']}\n" .
                              "Phone: {$decodedData['customer']['phone']}\n" .
                              "Shipping Address: {$decodedData['shipping_address']}\n"
                ]
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ]
        ]);

        // Check for success or failure
        if ($response->getStatusCode() == 200) {
            return ['status' => 'message_sent'];
        } else {
            // Handle failure response
            return ['status' => 'message_failed', 'error' => $response->getBody()->getContents()];
        }

        return response()->json(['success' => true], 200);


        // $data = $this->storeShopifyData($organizationId, $request);
        // return response()->json($data);
    }


    
}