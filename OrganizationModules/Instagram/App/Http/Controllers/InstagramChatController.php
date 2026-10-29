<?php

namespace OrganizationModules\Instagram\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatLog;
use App\Models\Contact;
use App\Models\OrganizationIntegration;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InstagramChatController extends Controller
{
    private $organizationId;

    public function __construct()
    {
        $this->organizationId = session()->get('current_organization');
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string',
            'message' => 'required|string',
        ]);
    
        // Fetch Contact
        $contact = Contact::where('uuid', $request->uuid)
            ->where('organization_id', session()->get('current_organization'))
            ->firstOrFail();
    
        $instagramUserId = data_get($contact, 'metadata.instagram_user_id');
        throw_if(!$instagramUserId, HttpResponseException::class, response()->json(['error' => 'Instagram user ID not found.'], 404));
    
        // Fetch Organization Integration
        $organizationIntegration = OrganizationIntegration::where('organization_id', session()->get('current_organization'))
            ->where('name', 'instagram')
            ->firstOrFail();
    
        $pageId = data_get($organizationIntegration, 'metadata.page_id');
        $accessToken = data_get($organizationIntegration, 'metadata.page_access_token');
    
        throw_if(!$pageId || !$accessToken, HttpResponseException::class, response()->json(['error' => 'Invalid Instagram integration settings.'], 401));
    
        // Send the message via API
        $responseData = $this->sendInstagramMessage($pageId, $instagramUserId, $request->message, $accessToken);
    
        if (!$responseData['success']) {
            return response()->json(['error' => $responseData['error']], 400);
        }
    
        // Store outbound message
        $this->createChatMessageForOutbound(
            $request->message,
            $responseData['message_id'],
            $instagramUserId,
            $organizationIntegration,
            $contact
        );
    
        return response()->json([
            'success' => true,
            'message_id' => $responseData['message_id'],
        ]);
    }
    
    /**
     * Send a message via Instagram API
     */
    private function sendInstagramMessage(string $pageId, string $instagramUserId, string $message, string $accessToken): array
    {
        $response = Http::post("https://graph.facebook.com/v20.0/{$pageId}/messages", [
            'recipient' => ['id' => $instagramUserId],
            'message' => ['text' => $message],
            'messaging_type' => 'RESPONSE',
            'access_token' => $accessToken,
        ]);
    
        Log::info('Instagram API Response', ['response' => $response->json()]);
    
        if ($response->failed()) {
            return ['success' => false, 'error' => $response->json()];
        }
    
        return [
            'success' => true,
            'message_id' => data_get($response->json(), 'message_id'),
        ];
    }
    
    /**
     * Store outbound Instagram chat message
     */
    private function createChatMessageForOutbound(
        string $messageText,
        string $messageId,
        string $recipientId,
        OrganizationIntegration $integration,
        Contact $contact
    ): void {
        $metadata = [
            'text' => $messageText,
            'sender_id' => $integration->instagram_business_id, // Your business account ID
            'recipient_id' => $recipientId
        ];
    
        $chat = new Chat();
        $chat->channel = 'instagram';
        $chat->old_message_id = null;
        $chat->imid = $messageId;
        $chat->uuid = Str::uuid();
        $chat->organization_id = $integration->organization_id;
        $chat->contact_id = $contact->id;
        $chat->type = 'outbound';
        $chat->metadata = json_encode($metadata);
        $chat->status = 'sent';
        $chat->is_read = false;
        $chat->created_at = now();
        $chat->is_pinned = false;
        $chat->is_starred = false;
        $chat->save();
    
        Log::info('Outbound message stored', ['chat_id' => $chat->id]);
    
        ChatLog::create([
            'contact_id' => $contact->id,
            'entity_type' => 'chat',
            'entity_id' => $chat->id,
            'created_at' => now()
        ]);
    }
    
}