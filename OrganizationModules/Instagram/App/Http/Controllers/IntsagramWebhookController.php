<?php 

namespace OrganizationModules\Instagram\App\Http\Controllers;

use App\DTO\InstagramMessageData;
use App\DTO\InstagramReadReceiptData;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatLog;
use App\Models\ChatMedia;
use App\Models\Contact;
use App\Models\OrganizationIntegration;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IntsagramWebhookController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }
    
    /**
     * Handle incoming webhook request.
     */
    public function handle(Request $request)
    {
        try {
            if ($request->isMethod('post')) {
                Log::info('Instagram new message', ['payload' => $request->getContent()]);
                return $this->processWebhook($request->all());
            }
            
            if ($request->isMethod('get')) {
                return $this->verifyWebhook($request);
            }
            
            Log::error('Invalid request method');
        } catch (\Exception $e) {
            Log::error('Instagram webhook error: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all()
            ]);
            throw $e;
        }
    }

    /**
     * Process the entire webhook payload.
     * 
     * Instead of using the messaging recipient ID to look up the integration,
     * we use the entry's "id" (the Instagram Business Account ID) so that each vendor’s
     * webhook event is correctly routed.
     */
    private function processWebhook(array $payload): void
    {
        if (!isset($payload['object']) || $payload['object'] !== 'instagram') {
            throw new \Exception('Invalid webhook object type');
        }

        foreach ($payload['entry'] as $entry) {
            // The entry "id" should be the Instagram Business Account ID.
            $businessId = $entry['id'] ?? null;
            if (!$businessId) {
                Log::error('Missing Instagram Business Account ID in entry');
                continue;
            }
            // Look up the organization integration using the Instagram Business Account ID.
            $organizationIntegration = $this->getOrganizationIntegration($businessId);
            if (!$organizationIntegration) {
                Log::warning('Organization integration not found for business id', ['business_id' => $businessId]);
                continue;
            }
            if (!isset($entry['messaging'])) {
                continue;
            }
            // Process each messaging event using the determined integration.
            foreach ($entry['messaging'] as $messaging) {
                $this->processMessagingEvent($messaging, $organizationIntegration);
            }
        }
    }

    /**
     * Process individual messaging event, passing along the integration record.
     */
    private function processMessagingEvent(array $messaging, OrganizationIntegration $organizationIntegration): void
    {
        if (isset($messaging['message'])) {
            $this->handleMessage($messaging, $organizationIntegration);
        } elseif (isset($messaging['read'])) {
            $this->handleReadReceipt($messaging);
        }
    }

    /**
     * Handle an incoming message event.
     */
    private function handleMessage(array $messaging, OrganizationIntegration $organizationIntegration): void
    {
        $messageData = new InstagramMessageData($messaging);
        
        // Now that we've already looked up the integration via the business ID,
        // we can skip using the recipient ID from the messaging payload.
        if (!$organizationIntegration) {
            Log::warning('Organization integration not found', ['received data' => $messageData]);
            return;
        }

        $contact = $this->findOrCreateContact($messageData->senderId, $organizationIntegration);
        if (!$contact) {
            Log::error('Failed to process contact', ['sender_id' => $messageData->senderId]);
            return;
        }

        $this->createChatMessage($messageData, $organizationIntegration, $contact);
    }

    /**
     * Create chat message in the database.
     */
    private function createChatMessage(InstagramMessageData $messageData, OrganizationIntegration $integration, Contact $contact): void
    {
        // Prepare metadata for the chat record.
        $metadata = [
            'from' => $messageData->senderId,
            'id' => $messageData->messageId,
            'timestamp' => now()->timestamp,
            'type' => 'text',
        ];
    
        if ($messageData->text) {
            $metadata = [
                'type' => 'text',
                'text' => [
                    'body' => $messageData->text
                ]
            ];
        }
    
        $hasMedia = false;
    
        if ($messageData->mediaType && $messageData->mediaData) {
            $hasMedia = true;
            $metadata = [
                'type' => $messageData->mediaType,
                $messageData->mediaType => [
                    'url' => $messageData->mediaData['url'],
                    'id' => $messageData->mediaData['id']
                ]
            ];
        }
    
        $chat = new Chat();
        $chat->channel = 'instagram';
        $chat->old_message_id = null;
        $chat->imid = $messageData->messageId;
        $chat->uuid = Str::uuid();
        $chat->organization_id = $integration->organization_id;
        $chat->contact_id = $contact->id;
        $chat->type = 'inbound';
        $chat->metadata = json_encode($metadata);
        $chat->status = 'delivered';
        $chat->is_read = false;
        $chat->created_at = now();
        $chat->is_pinned = false;
        $chat->is_starred = false;
    
        if ($messageData->replyToMessageId) {
            $originalMessage = Chat::where('imid', $messageData->replyToMessageId)->first();
            $chat->old_message_id = optional($originalMessage)->id;
        }
    
        $chat->save();
    
        if ($hasMedia) {
            $chatMedia = ChatMedia::create([
                'name' => rand(1000, 9999),
                'path' => $messageData->mediaData['url'],
                'location' => 'remote',
                'size' => 0,
                'type' => $messageData->mediaType,
                'created_at' => now(),
            ]);

            $chat->media_id = $chatMedia->id;
            $chat->save();
        }
    
        $chatlogId = ChatLog::insertGetId([
            'contact_id' => $contact->id,
            'entity_type' => 'chat',
            'entity_id' => $chat->id,
            'created_at' => now()
        ]);
    
        $chatLogArray = ChatLog::where('id', $chatlogId)
            ->whereNull('deleted_at')
            ->first();
        $chatArray = [
            [
                'type' => 'chat',
                'value' => $chatLogArray->relatedEntities
            ]
        ];
    }

    /**
     * Handle message read receipts.
     */
    private function handleReadReceipt(array $messaging): void
    {
        $readData = new InstagramReadReceiptData($messaging);
        
        DB::table('chats')
            ->where('imid', $readData->imid)
            ->update(['is_read' => true, 'status' => 'read']);
    }

    /**
     * Look up the organization integration using the Instagram Business Account ID.
     * 
     * Note: This assumes your OrganizationIntegration record stores the Instagram Business Account ID
     * in the metadata under the key "instagram_user_id".
     */
    private function getOrganizationIntegration(string $instagramBusinessId): ?OrganizationIntegration
    {
        return OrganizationIntegration::where('metadata->instagram_user_id', $instagramBusinessId)->first();
    }

    /**
     * Find or create a contact for the sender.
     */
    private function findOrCreateContact(string $senderId, OrganizationIntegration $organizationIntegration)
    {
        // Decode organization metadata
        $config = $organizationIntegration && is_string($organizationIntegration?->metadata)
            ? json_decode($organizationIntegration->metadata, true)
            : (is_array($organizationIntegration?->metadata) ? $organizationIntegration->metadata : []);
    
        // Get the organization's Instagram account ID
        $organizationInstagramId = $config['instagram_account_id'] ?? null;
    
        // If the sender is the same as the organization's Instagram account, do nothing
        if ($senderId === $organizationInstagramId) {
            Log::info('Ignoring message from own Instagram account.', ['senderId' => $senderId]);
            return null;
        }
    
        // Check if contact already exists
        $contact = Contact::where('organization_id', $organizationIntegration->organization_id)
            ->whereJsonContains('metadata->instagram_user_id', $senderId)
            ->first();
    
        $accessToken = $config['long_lived_access_token'] ?? null;
    
        if ($contact) {
            return $contact;
        }
    
        // Fetch Instagram username
        $username = $this->fetchInstagramUsername($senderId, $accessToken);
        Log::info('Instagram username fetched', ['username' => $username]);
    
        // Create new contact
        return Contact::create([
            'uuid' => Str::uuid(),
            'organization_id' => $organizationIntegration->organization_id,
            'first_name' => $username ?? 'Instagram User',
            'latest_chat_created_at' => now(),
            'metadata' => [
                'instagram_user_id' => $senderId,
                'instagram_username' => $username ?? 'Instagram User',
                'source' => 'instagram',
            ],
            'created_by' => 0,
        ]);
    }
    
    
    /**
     * Fetch the Instagram username using the Graph API.
     */
    private function fetchInstagramUsername(string $instagramScopedId, string $accessToken)
    {
        // Build the endpoint URL using the Instagram-scoped ID and the required fields.
        $url = "https://graph.instagram.com/v20.0/{$instagramScopedId}?fields=name,username,profile_pic,follower_count,is_user_follow_business,is_business_follow_user&access_token={$accessToken}";
        
        Log::info("Fetching Instagram profile info", ['url' => $url]);
        
        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            Log::info("Fetched Instagram profile info", ['data' => $data]);
            return $data['username'] ?? null;
        } catch (\Exception $e) {
            Log::error("Failed to fetch Instagram profile info for ID {$instagramScopedId}: " . $e->getMessage());
            return null;
        }
    }
    
    
    
    /**
     * (Optional) Verify webhook challenge (for GET requests).
     * Implement this method based on your verification requirements.
     */
    private function verifyWebhook(Request $request)
    {
        // For example, Facebook sends a hub.challenge parameter to verify the webhook.
        $challenge = $request->input('hub_challenge');
        if ($challenge) {
            return response($challenge, 200);
        }
        return response('Verification failed', 400);
    }
}