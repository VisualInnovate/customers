<?php /** @noinspection ALL */

namespace Modules\Shopify\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Controller as BaseController;
use App\Models\Organization;
use App\Models\Template;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends BaseController
{
    public function __construct(private readonly ApiController $controller) {}
    public function handleWebhook(Request $request): JsonResponse
    {
        // Handle Shopify webhook events here
        // You can access the request data using $request->all()
        // and process it according to your application's needs.

        // Example: Log the request data
//        if(!$this->verifyShopifyToken($request->getContent(), $request->header('X-Shopify-Hmac-Sha256'))) {
//            return response()->json(['error' => 'Unauthorized'], 401);
//        }
        $contact = $request->customer ?? [];
        $phone = $request->phone ?? $contact['phone'] ?? '';
        $order_name = $request->name;
        $status_url = $request->order_status_url;
        $domain = $request->header('x-shopify-shop-domain');
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $topic = $request->header('X-Shopify-Topic');
        $token = $request->token;

        [$org, $integration_metadata] = $this->getVerifiedOrganizationFromDomainWithMetadata($domain, $request->getContent(), $hmac);
        if (!$org || !$integration_metadata) {
            Log::error('Organization not found or invalid metadata for domain: ' . $domain);
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $template = $this->getTemplateByTopic($topic, $org->id);
        try {
            if(!empty($phone) || !empty($template)) {
                $response = $this->sendWhatsAppTemplate(
                    $org->id,
                    $phone,
                    $template->name,
                    $template->language,
                    $order_name,
                    $status_url,
                    $token,
                    $contact['first_name'] ?? '',
                    $contact['last_name'] ?? '',
                    $contact['email'] ?? ''
                );
                Log::info('WhatsApp Template Sent: ' . $response->getContent());
            } else
                Log::info('No phone number or template attached with this webhook: ' . $request->getContent());
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp template: ' . $e->getMessage(), $e->getTrace());
        }

        // Return a response to acknowledge receipt of the webhook
        return response()->json(['status' => 'success']);
    }

    public function getVerifiedOrganizationFromDomainWithMetadata($domain, $data, $hmac): ?array
    {
        $record = DB::table('organization_integrations')
            ->select(['organization_id', 'metadata'])
            ->where('name', 'Shopify')
            ->where('active', true)
            ->where('metadata->domain', $domain)
            ->first();
        if(!$record) return null;
        $metadata = json_decode($record->metadata);
        return $this->verifyShopifyToken($data, $hmac, $metadata->api_key)
            ? [Organization::find($record->organization_id), $metadata]
            : null;
    }
    public function verifyShopifyToken($data, $hmac_header, $app_key): bool
    {
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, $app_key, true));
        return hash_equals($hmac_header, $calculated_hmac);
    }

    public function sendWhatsAppTemplate($organizationId, $phone, $templateName, $templateLang, $orderNumber,
                                         $statusUrl, $token, $firstName = null, $lastName = null, $email = null): JsonResponse
    {
        $request = new Request([
            'organization' => $organizationId,
            'phone' => $phone,
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $templateLang
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $firstName ?? ''
                            ],
                            [
                                'type' => 'text',
                                'text' => $orderNumber
                            ],
                        ]
                    ],
                    [
                        "type" => "button",
                        "sub_type" => "url",
                        "index" => 0,
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $this->transformShopifyUrl($statusUrl, $token)
                            ]
                        ]
                    ]
                ]
            ],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]);

        return $this->controller->sendTemplateMessage($request);
    }
    function transformShopifyUrl(string $authUrl, string $orderId): ?string {
        $matches = [];
        preg_match('#/(\d+)/orders/#', $authUrl, $matches);
        if (!isset($matches[1])) {
            return null;
        }
        $shopId = $matches[1];
        return "$shopId/account/orders/$orderId";
    }

    public function getTemplateByTopic($topic, $org_id)
    {
        $name = 'shopify_' . str_replace('/', '_', strtolower($topic));
        return $template = Template::where('organization_id', $org_id)
            ->where('name', $name)
            ->first();
    }
}
