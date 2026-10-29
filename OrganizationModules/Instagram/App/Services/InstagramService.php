<?php

namespace OrganizationModules\Instagram\App\Services;

use App\Events\NewChatEvent;
use App\Helpers\WebhookHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Chat;
use App\Models\ChatLog;
use App\Models\ChatMedia;
use App\Models\Contact;
use App\Models\OrganizationIntegration;

class InstagramService
{
    private ?string $accessToken;
    private string $apiVersion = 'v20.0';
    private ?string $pageId;
    private string $organizationId;

    public function __construct($accessToken, $pageId, $organizationId)
    {
        $this->accessToken = $accessToken;
        $this->pageId = $pageId;
        $this->organizationId = $organizationId;
    }

    private function setHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }

    public function sendInstagramMessage(
        string $contactUuid,
        string $messageContent,
        string $type = 'text',
        ?string $mediaUrl = null,
        ?string $replyToMessageId = null
    ) {
        $contact = Contact::where('uuid', $contactUuid)->first();
        Log::info('Contact retrieved', ['contact' => $contact]);
        if (!$contact) {
            return ['success' => false, 'error' => 'Invalid contact'];
        }
    
        // Retrieve the recipient's Instagram-scoped ID from the contact metadata.
        $contactMetadata = is_string($contact->metadata) ? json_decode($contact->metadata, true) : $contact->metadata;
        $recipientIgId = data_get($contactMetadata, 'instagram_user_id');
        Log::info('Contact Instagram metadata', ['recipientIgId' => $recipientIgId]);
        if (!$recipientIgId) {
            return ['success' => false, 'error' => 'Missing Instagram recipient ID'];
        }
    
        // Retrieve the sender's (our business account's) Instagram info from the integration record.
        $integration = OrganizationIntegration::where('organization_id', $contact->organization_id)
            ->where('name', 'instagram')
            ->first();
        if (!$integration) {
            return ['success' => false, 'error' => 'Instagram integration not found'];
        }
        $integrationMetadata = is_string($integration->metadata) ? json_decode($integration->metadata, true) : $integration->metadata;
        $senderIgId = data_get($integrationMetadata, 'instagram_user_id'); // Instagram professional account ID.
        $accessToken = data_get($integrationMetadata, 'long_lived_access_token');
        if (!$senderIgId || !$accessToken) {
            return ['success' => false, 'error' => 'Missing sender Instagram account info or access token'];
        }
    
        $url = "https://graph.instagram.com/v22.0/{$senderIgId}/messages";
        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'application/json'
        ];
    
        $requestData = [
            'recipient' => ['id' => $recipientIgId]
        ];
    
        if ($replyToMessageId !== null) {
            $requestData['message']['reply_to'] = $replyToMessageId;
        }
    
        if ($type === 'text') {
            $requestData['message'] = ['text' => $messageContent];
        } elseif (in_array($type, ['image', 'video', 'audio', 'document', 'sticker'])) {
            // For stickers
            if ($type === 'sticker') {
                $requestData['message'] = ['attachment' => ['type' => 'like_heart']];
            } else {
                $requestData['message'] = [
                    'attachment' => [
                        'type' => $type,
                        'payload' => [
                            'url' => $mediaUrl,
                            'is_reusable' => true
                        ]
                    ]
                ];
            }
        }
    
        Log::info('Sending Instagram message', ['url' => $url, 'requestData' => $requestData]);
    
        $response = Http::withHeaders($headers)->post($url, $requestData);
        $responseObject = $response->json();
        Log::info('Instagram API Response', ['response' => $responseObject]);
    
        if ($response->failed()) {
            return ['success' => false, 'error' => $responseObject];
        }
    
        $messageId = data_get($responseObject, 'message_id');
    
        Log::info('Instagram final response', ['response' => $responseObject]);
    
        // Check for echo message: if is_echo is true, do not create a new chat.
        if (isset($responseObject['is_echo']) && $responseObject['is_echo'] === true) {
            Log::info('is_echo detected. Not creating new chat.');
            return ['success' => true, 'message_id' => $messageId, 'chat' => null];
        }
    
        $chat = Chat::create([
            'channel' => 'instagram',
            'organization_id' => $contact->organization_id,
            'old_message_id' => $replyToMessageId,
            'imid' => $messageId,
            'contact_id' => $contact->id,
            'type' => 'outbound',
            'metadata' => json_encode([
                'from' => $senderIgId,
                'id' => $messageId,
                'timestamp' => now()->timestamp,
                'type' => $type,
                $type => ($type === 'text')
                    ? ['body' => $messageContent]
                    : [
                        'url' => $mediaUrl,
                        'id' => $messageId,
                    ],
            ]),
            'status' => 'sent',
            'user_id' => auth()?->user()?->id ?? null,
        ]);
    
        if (in_array($type, ['image', 'video', 'audio', 'document', 'sticker']) && $mediaUrl) {
            $mediaName = basename(parse_url($mediaUrl, PHP_URL_PATH));
            $media = ChatMedia::create([
                'name' => $mediaName,
                'path' => $mediaUrl,
                'location' => 'local',
                'type' => $type,
                'size' => 0,
                'created_at' => now()
            ]);
            $chat->media_id = $media->id;
            $chat->save();
        }
    
        $chatlogId = ChatLog::insertGetId([
            'contact_id' => $contact->id,
            'entity_type' => 'chat',
            'entity_id' => $chat->id,
            'created_at' => now()
        ]);
    
        $chatLogArray = ChatLog::where('id', $chatlogId)->whereNull('deleted_at')->first();
        $chatArray = [
            [
                'type' => 'chat',
                'value' => $chatLogArray->relatedEntities
            ]
        ];
    
        event(new NewChatEvent($chatArray, $contact->organization_id));
    
        return ['success' => true, 'message_id' => $messageId, 'chat' => $chat];
    }
    
    
    
    
}