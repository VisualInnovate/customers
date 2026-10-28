<?php

namespace Modules\Shopify\Controllers;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Addon;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SetupController extends BaseController
{
    public function store(Request $request){
        $request->validate([
            'shopify_domain' => 'required',
            'shopify_token' => 'required|string',
        ]);
        $org_id = session('current_organization', null);
        $organization = Organization::find($org_id);

        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        $addon = Addon::where('name', 'Shopify')->first();
        if (!$addon) {
            return response()->json(['error' => 'Addon not found'], 404);
        }



        DB::table('organization_integrations')->updateOrInsert([
            'organization_id' => $organization->id,
            'name' => 'Shopify',
        ], [ // add organization keys with shopify
            'metadata' => json_encode([
                'domain' => $request->shopify_domain,
                'api_key' => $request->shopify_token,
            ]),
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Shopify Integration completed successfully']);
    }

    public function createShopifyTemplate(Request $request) {
        $org = Organization::find(session('current_organization', null));
        if (!$org) {
            return response()->json(['error' => 'Organization not found'], 404);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'order_message' => 'required|string|max:500',
        ]);
        // Template
        $template = [
            "language" => "en",
            "category" => "UTILITY",
            "components" => [
                [
                    "type" => "BODY",
                    "example" => [
                        "body_text" => [
                            [
                                "Mark",
                                "Tuscan Getaway package"
                            ]
                        ]
                    ]
                ],
                [
                    "type" => "FOOTER",
                    "text" => "Thanks for ordering with us"
                ],
                [
                    "type" => "BUTTONS",
                    "buttons" => [
                        [
                            "type" => "URL",
                            "text" => "See Your Order",
                            "url" => "https://shopify.com/{{1}}",
                            "example" => [
                                "summer2023"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $template["name"] = $request->input('name');
        $template["components"][0]["text"] = $request->input('order_message');


        $metadata = json_decode($org->metadata);
        $response = Http::withToken($metadata->whatsapp->access_token)
            ->post('https://graph.facebook.com/' . config('graph.api_version') . '/' . $metadata->whatsapp->waba_id . '/message_templates', $template);

        if ($response->failed()) {
            Log::error('Failed to create Shopify template: ' . $template['name'] . ' for organization ID: ' . $org->id, [
                'response' => $response->json(),
                'status_code' => $response->status(),
            ]);
            return response()->json([
                'error' => 'Failed to create Shopify template',
                'message' => $response->json()['error']['error_user_msg'] ?? 'An error occurred while creating the template.',
            ], $response->status());
        }
        Log::info('Shopify template created successfully: ' . $template['name'] . ' for organization ID: ' . $org->id);
        Log::info('Template Body: ', $template);

        return response()->json([
            'message' => 'Shopify template created successfully',
        ]);
    }
}
