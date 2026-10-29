<?php

namespace OrganizationModules\LiveChat\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrganizationIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessagesController extends Controller
{
    // ChatController.php
    public function generateEmbedCode()
    {
        $integration = OrganizationIntegration::where('organization_id', session('current_organization'))
            ->where('name', 'LiveChat')
            ->first();

        return response()->json([
            'code' => <<<HTML
        <script>
            window.LiveChatConfig = {
                apiKey: '{$integration->metadata['api_key']}'
            };
        </script>
        <script src="{$_ENV['APP_URL']}/js/chat-widget.js"></script>
        HTML
            ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
