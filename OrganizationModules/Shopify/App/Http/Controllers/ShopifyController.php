<?php

namespace OrganizationModules\Shopify\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Organization;
use App\Models\OrganizationIntegration;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use OrganizationModules\Shopify\App\Http\Requests\StoreDataRequest;
use OrganizationModules\Shopify\Services\DataService;
use Inertia\Inertia;
use Modules\IntelliReply\Models\Document;
use Modules\IntelliReply\Resources\DocumentResource;

class ShopifyController extends Controller
{
    protected $shopifyService;
    public function __construct(DataService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    public function redirectToShopify(Request $request)
    {
        $shop = $request->input('domain');
        $redirectUrl = $this->shopifyService->getShopifyOAuthUrl($shop);
        
        return response()->json(['redirectUrl' => $redirectUrl]);
    }

    public function handleShopifyCallback(Request $request)
    {
        $code = $request->input('code');
        $shop = $request->input('shop');
    
        if (!$code || !$shop) {
            return response()->json(['error' => 'Missing code or shop'], 400);
        }
    
        $tokenUrl = "https://$shop/admin/oauth/access_token";
    
        $payload = [
            'client_id' => '9f5bb787d6bcbd1ed4f19790fb71fbdb',
            'client_secret' => '8f5ae10434ae7858bddde185cb3f4aae',
            'code' => $code,
        ];
    
        $response = Http::post($tokenUrl, $payload);
        if ($response->failed()) {
            return response()->json(['error' => 'Failed to exchange code for access token'], 500);
        }
    
        $accessToken = $response->json()['access_token'];
    
        $webhookResponse = $this->registerOrderCreateWebhook($shop, $accessToken);
    
        if ($webhookResponse) {
            $data = [
                'shop_domain' => $shop,
                'access_token' => $accessToken,
                'credentials' => [
                    'webhook' => $webhookResponse['webhook'],
                ],
            ];
    
            OrganizationIntegration::updateOrCreate(
                [
                    'organization_id' => session()->get('current_organization'),
                    'name' => 'shopify',
                ],
                [
                    'metadata' => json_encode($data),
                    'active' => true,
                ]
            );
        }
    
        return Redirect::route('integrations')->with(
            'status', [
                'type' => 'success', 
                'message' => __('shopify conected successfully')
            ]
        );
    }
    
    public function registerOrderCreateWebhook($shop, $accessToken)
    {
        $webhookUrl = 'https://app.customercloudhq.com/webhooks/' . session()->get('current_organization') . '/shopify';
        $webhookTopic = 'orders/create';

        $existingWebhooks = $this->getExistingWebhooks($shop, $accessToken);

        // Check if a webhook with the same address and topic already exists
        foreach ($existingWebhooks as $webhook) {
            if ($webhook['address'] === $webhookUrl && $webhook['topic'] === $webhookTopic) {
                Log::info('Webhook already exists.', ['webhook' => $webhook]);
    
                // Option 1: Update the existing webhook (if needed)
                // $this->updateWebhook($shop, $accessToken, $webhook['id']);
    
                // Option 2: Delete the existing webhook and register a new one
                $this->deleteWebhook($shop, $accessToken, $webhook['id']);
                break;
            }
        }
    
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->post("https://$shop/admin/api/2023-10/webhooks.json", [
            'webhook' => [
                'topic' => $webhookTopic,
                'address' => $webhookUrl,
                'format' => 'json',
            ],
        ]);
    
        if ($response->successful()) {
            $webhookData = $response->json();
            Log::info('Webhook registered successfully.', ['response' => $webhookData]);
            return $webhookData;
        } else {
            Log::error('Failed to register webhook.', ['response' => $response->body()]);
            return null;
        }
    }

    private function getExistingWebhooks($shop, $accessToken)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->get("https://$shop/admin/api/2023-10/webhooks.json");

        if ($response->successful()) {
            return $response->json()['webhooks'];
        } else {
            Log::error('Failed to fetch existing webhooks.', ['response' => $response->body()]);
            return [];
        }
    }

    private function deleteWebhook($shop, $accessToken, $webhookId)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
        ])->delete("https://$shop/admin/api/2023-10/webhooks/$webhookId.json");

        if ($response->successful()) {
            Log::info('Webhook deleted successfully.', ['webhookId' => $webhookId]);
            return true;
        } else {
            Log::error('Failed to delete webhook.', ['webhookId' => $webhookId, 'response' => $response->body()]);
            return false;
        }
    }

    public function show(Request $request, $id = null)
    {
        $data = $this->shopifyService->getShopifyData($request, session()->get('current_organization'), $id);
        return response()->json($data);
    }
    public function index(Request $request)
    { 
        $organizationId = session()->get('current_organization');
        $model = new Document();
        $searchTerm = $request->query('search');
        $organization = Organization::where('id', $organizationId)->first();

        $data['title'] = __('Settings');
        $decodedData = $organization ? json_decode($organization->metadata, true) : [];
        $aiSettings = $organization ? $decodedData['ai'] ?? [] : [];

        // Fetch integrations
        $data['rows'] = OrganizationIntegration::where('organization_id', $organizationId)
            ->select('id', 'name', 'active', 'metadata')
            ->get();

        // Add AI settings as a separate row if it exists
        if (!empty($aiSettings)) {
            $data['rows']->push([
                'id' => 'ai_settings', // Unique identifier
                'name' => 'AI Settings',
                'active' => true, // Or any relevant status
                'ai' => $aiSettings
            ]);
        }
        $data['settings'] = $organization;
        $data['timezones'] = config('formats.timezones');
        $data['countries'] = config('formats.countries');
        $data['sounds'] = config('sounds');
        $data['filters'] = request()->all();

        $aiAssistantAddon = Addon::where('name', 'AI Assistant')->first();
        $aiAssistantSetting = Setting::where('key', 'ai_assistant')->first();

        $data['aimodule'] = $aiAssistantAddon && $aiAssistantAddon->status && $aiAssistantSetting && $aiAssistantSetting->value == 1;
        return Inertia::render('User/Integrations/Index', $data);
    }

    public function handle(Request $request, $organizationId)
    {
        Log::info('Shopify Data: ' . json_encode($request->all()));

        // return response()->json(['success' => true], 200);

        $integration = OrganizationIntegration::where('organization_id', $organizationId)->where('name', 'shopify')->first();
        $metadata = json_decode($integration->metadata, true);
        $shopUrl = $metadata['shop_domain'];
        $accessToken = $metadata['access_token'];
        $client = new Client();
        
        $decodedData = $decodedData = $request->all();
        if (isset($decodedData['customer']['id'])) {
            $customerId = $decodedData['customer']['id'];
            $customerUrl = "https://{$shopUrl}/admin/api/2024-10/customers/{$customerId}.json";
    
            // Fetch the customer data
            $customerResponse = $client->request('GET', $customerUrl, [
                'headers' => [
                    'X-Shopify-Access-Token' => $accessToken
                ]
            ]);
            
            $customerData = json_decode($customerResponse->getBody()->getContents(), true);
            $phone = $customerData['customer']['phone'] ?? null;
            $firstName = $customerData['customer']['first_name'] ?? null;
            $lastName = $customerData['customer']['last_name'] ?? null;
            Log::info('Customer Data: ' . json_encode($customerData));
            // Log::error('Customer ID not found in the provided data.');
            // return response()->json(['error' => 'Customer ID not found in the provided data.'], 404);
            $data = $this->shopifyService->handleShopifyWebhook($accessToken, $decodedData, $organizationId, $phone, $firstName, $lastName);
            return response()->json($data);
        }

        return response()->json(['error' => 'Customer ID not found in the provided data.'], 404);

        // return response()->json(['success' => true], 200);

        // $phone = $decodedData['shipping_lines']['phone'];
    }
    
}