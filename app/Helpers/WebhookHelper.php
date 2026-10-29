<?php 

namespace App\Helpers;

use App\Models\Addon;
use App\Models\Setting;

class WebhookHelper
{
    public static function triggerWebhookEvent($event, $data, $organizationId = NULL)
    {
        $organizationId = $organizationId = NULL ? session()->get('current_organization') : $organizationId;
        $webhookAddon = Addon::where('name', 'Webhooks')->first();
        $webhookSetting = Setting::where('key', 'webhook')->first();
        $webhookModule = $webhookAddon && $webhookAddon->status && $webhookSetting && $webhookSetting->value == 1;

        if($webhookModule){
            // Check if MainController exists
            if (class_exists(\Modules\Webhook\Controllers\MainController::class)) {
                $webhookController = new \Modules\Webhook\Controllers\MainController();
                return $webhookController->trigger($event, $organizationId, $data);
            }

            // Handle the case where the class doesn't exist
            return response()->json(['error' => 'Webhook controller not found'], 404);
        }
    }
}
