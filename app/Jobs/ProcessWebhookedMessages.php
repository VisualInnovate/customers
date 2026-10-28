<?php

namespace App\Jobs;

use App\Events\NewChatEvent;
use App\Helpers\UnreadCountHelper;
use App\Helpers\WebhookHelper;
use App\Models\Chat;
use App\Models\ChatLog;
use App\Models\ChatMedia;
use App\Models\ChatStatusLog;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\AutoReplyService;
use App\Services\ChatService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;

class ProcessWebhookedMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(private $res,
                                private Organization $organization,
                                private $silent = false,
                                private $thread_phone = null,
                                private $mark_contact_chat_read = false)
    {
        $this->onQueue('messages');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $contacts = $this->res['value']['contacts'][0] ?? null;
        $messages = $this->res['value']['messages'] ?? null;
        $statuses = $this->res['value']['statuses'] ?? null;
        $metadata = json_decode($this->organization->metadata);
        $org_phone = $metadata?->whatsapp?->display_phone_number ?? null;
        if (!$org_phone) {
            Log::error('Organization phone number not found in metadata for organization: ' . $this->organization->id);
            return;
        }
        $org_timezone = $metadata?->timezone ?? 'UTC';
        $org_phone = sanitize_phone($org_phone);

        if($statuses) {
            //$response = $res['value']['statuses'][0];
            foreach($statuses as $response){
                $chatWamId = $response['id'];
                $status = $response['status'];

                $chat = Chat::where('wam_id', $chatWamId)->first();

                if($chat){
                    $oldStatus = $chat->status;
                    $oldIsRead = $chat->is_read;

                    $chat->status = $status;
                    $chat->is_read = $status === 'read' ? 1 : 0; // Mark as read if status is 'read'
                    $chat->save();

                    // Decrement unread count when inbound message is marked as read
                    if ($chat->type === 'inbound' && $status === 'read' && !$oldIsRead) {
                        UnreadCountHelper::resetUnreadCount($chat->contact_id);
                    }

                    $chatStatusLog = new ChatStatusLog;
                    $chatStatusLog->chat_id = $chat->id;
                    $chatStatusLog->metadata = json_encode($response);
                    $chatStatusLog->save();
                }
            }

            if(!$this->silent) {
                // Trigger webhook
                WebhookHelper::triggerWebhookEvent('message.status.update', [
                    'data' => $this->res,
                ], $this->organization->id);
            }
        } else if($messages) {
            $isLimitReached = SubscriptionService::isSubscriptionLimitReachedForInboundMessages($this->organization->id);
            //Log::info($messages);

            if(!$isLimitReached){
                foreach($messages as $response){
                    $inbound = !isset($response['to']);
                    $phone = $inbound ? $response['from'] : $response['to'] ?? $response['from'];

                    $phone = sanitize_phone($phone);
                    if($inbound && isset($respone['to'])) { // check again if the msg is inbound and the phone number is from is the same as the organization wp number
                        if($phone->equals($org_phone)) {
                            $phone = sanitize_phone($response['to']); // in case of from is org_phone make phone the to number
                            $inbound = false;
                        }
                    } else if($inbound && $this->thread_phone != null && (($respone['history_context']['from_me'] ?? false) || $phone->equals($org_phone))) {
                        $inbound = false;
                        $phone = sanitize_phone($this->thread_phone);
                    }
                    $phone = $phone->formatE164();

                    //Check if contact exists in organization
                    $contact = Contact::where('organization_id', $this->organization->id)->where('phone', $phone)->whereNull('deleted_at')->first();
                    $isNewContact = false;

                    if(!$contact){
                        //Create a contact
                        $contactData = $this->res['value']['contacts'][0]['profile'] ?? null;

                        $contact = Contact::create([
                            'first_name' => $contactData['name'] ?? null,
                            'last_name' => null,
                            'email' => null,
                            'phone' => $phone,
                            'organization_id' => $this->organization->id,
                            'created_by' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $isNewContact = true;
                    }

                    if($contact){
                        $contact->update(['deleted_at' => null]);

                        $contactData = $this->res['value']['contacts'][0]['profile'] ?? null;
                        if($contact->first_name == NULL && $contactData != null){
                            $contact->update([
                                'first_name' => $contactData['name'],
                            ]);
                        }

                        if($this->mark_contact_chat_read && !$inbound && (!$contact->lastChat?->is_read ?? false)) {
                            // Mark contact chat as read
                            $updatedCount = DB::table('chats')
                                ->where('contact_id', $contact->id)
                                ->whereNull('deleted_at')
                                ->where('organization_id', $this->organization->id)
                                ->where('is_read', 0)
                                ->where('type', 'inbound')
                                ->update(['is_read' => 1, 'status' => 'read']);

                            // Reset unread count since all messages are marked as read
                            if ($updatedCount > 0) {
                                UnreadCountHelper::resetUnreadCount($contact->id);
                            }
                        }

                        $chat = Chat::where('wam_id', $response['id'])->where('organization_id', $this->organization->id)->first();

                        $newChat = false;
                        if(!$chat) {
                            //First open the chat
                            (new ChatService($this->organization->id))->handleTicketAssignment($contact->id);
                            $chat = new Chat;
                            $chat->organization_id = $this->organization->id;
                            $chat->wam_id = $response['id'];
                            $chat->contact_id = $contact->id;
                            $chat->type = $inbound ? 'inbound' : 'outbound';
                            $chat->metadata = json_encode($response);
                            $chat->status = isset($response['history_context']) ? strtolower($response['history_context']['status']) : 'delivered'; // read status in case of history messages are provided
                            $chat->created_at = Carbon::createFromTimestamp($response['timestamp'] ?? now(), $org_timezone);
                            $chat->save();

                            // Update unread count based on message type
                            if ($inbound) {
                                // Increment for new inbound message
                                UnreadCountHelper::incrementUnreadCount($contact->id);
                            } else {
                                // Reset for new outbound message (user replied)
                                UnreadCountHelper::resetUnreadCount($contact->id);
                            }
                        }
                        if($chat){
                            if($response['type'] === 'image' || $response['type'] === 'video' || $response['type'] === 'audio' || $response['type'] === 'document' || $response['type'] === 'sticker'){
                                $type = $response['type'];
                                $mediaId = $response[$type]['id'];

                                //Get & Download media file
                                $media = $this->getMedia($mediaId, $this->organization);
                                $downloadedFile = $this->downloadMedia($media, $this->organization);

                                //Upload media
                                $chatMedia = new ChatMedia;
                                $chatMedia->name = $type === 'document' ? $response[$type]['filename'] : 'N/A';
                                $chatMedia->path = $downloadedFile['media_url'];
                                $chatMedia->type = $media['mime_type'];
                                $chatMedia->size = $media['file_size'];
                                $chatMedia->location = $downloadedFile['location'];
                                $chatMedia->created_at = now();
                                $chatMedia->save();

                                //Update chat
                                Chat::where('id', $chat->id)->update([
                                    'media_id' => $chatMedia->id
                                ]);
                            }
                        }

                        $chat = Chat::with('contact','media')->where('id', $chat->id)->first();

                        $chatlogId = ChatLog::insertGetId([
                            'contact_id' => $contact->id,
                            'entity_type' => 'chat',
                            'entity_id' => $chat->id,
                            'created_at' => Carbon::createFromTimestamp($response['timestamp'] ?? now(), $org_timezone),
                        ]);

                        $chatLogArray = ChatLog::where('id', $chatlogId)->where('deleted_at', null)->first();
                        $chatArray = array([
                            'type' => 'chat',
                            'value' => $chatLogArray->relatedEntities
                        ]);

                        try {
                            if(!$this->silent)
                                event(new NewChatEvent($chatArray, $this->organization->id));
                        } catch (Exception $e) {
                            Log::error('Error triggering NewChatEvent: ' . $e->getMessage(), $chatArray);
                        }

                        $isMessageLimitReached = SubscriptionService::isSubscriptionFeatureLimitReached($this->organization->id, 'message_limit');

                        if(!$isMessageLimitReached){
                            if($response['type'] === 'text' || $response['type'] === 'button'|| $response['type'] === 'audio'|| $response['type'] === 'interactive'){
                                (new AutoReplyService)->checkAutoReply($chat, $isNewContact);
                            }
                        }
                    }
                }

                if (!$this->silent) {
                    // Trigger webhook
                    WebhookHelper::triggerWebhookEvent('message.received', [
                        'data' => $this->res,
                    ], $this->organization->id);
                }
            }
        }
    }

    private function downloadMedia($mediaInfo, Organization $organization)
    {
        $metadata = json_decode($organization->metadata);

        if (empty($metadata) || empty($metadata->whatsapp->access_token)) {
            return $this->forbiddenResponse();
        }

        try {
            $client = new Client();

            $requestOptions = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $metadata->whatsapp->access_token,
                    'Content-Type' => 'application/json',
                ],
            ];

            $response = $client->request('GET', $mediaInfo['url'], $requestOptions);

            $fileContent = $response->getBody();
            $mimeType = $mediaInfo['mime_type'] ?? 'application/octet-stream'; // Default fallback
            $fileName = $this->generateFilename($fileContent, $mediaInfo['mime_type']);

            $storage = Setting::where('key', 'storage_system')->first()->value;

            if($storage === 'local'){
                $location = 'local';
                $file = Storage::disk('local')->put('public/' . $fileName, $fileContent);
                $mediaFilePath = $file;
                $mediaUrl = rtrim(config('app.url'), '/') . '/media/' . 'public/' . $fileName;
            } else if($storage === 'aws') {
                $location = 'amazon';
                $filePath = 'uploads/media/received/'  . $organization->id . '/' . Str::random(40) . time();
                $file = Storage::disk('s3')->put($filePath, $fileContent, [
                    'ContentType' => $mimeType
                ]);
                $mediaUrl = Storage::disk('s3')->url($filePath);
            }

            $mediaData = [
                'media_url' => $mediaUrl,
                'location' => $location,
            ];

            return $mediaData;
        } catch (RequestException $e) {
            Log::error("Error processing webhook: " . $e->getMessage());
            return Response::json(['error' => 'Failed to download file'], 403);
        }
    }

    private function generateFilename($fileContent, $mimeType): string
    {
        // Generate a unique filename based on the file content
        $hash = sha1($fileContent);

        // Get the file extension from the media type
        $extension = explode('/', $mimeType)[1];

        // Combine the hash, timestamp, and extension to create a unique filename
        $filename = "{$hash}_" . time() . ".{$extension}";

        return $filename;
    }

    private function getMedia($mediaId, Organization $organization)
    {
        $metadata = json_decode($organization->metadata);

        if (empty($metadata) || empty($metadata->whatsapp->access_token)) {
            return $this->forbiddenResponse();
        }

        $client = new Client();
        $responseObject = new \stdClass();

        try {
            $requestOptions = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $metadata->whatsapp->access_token,
                    'Content-Type' => 'application/json',
                ],
            ];

            $response = $client->request('GET', "https://graph.facebook.com/v18.0/{$mediaId}", $requestOptions);

            return json_decode($response->getBody()->getContents(), true);
        } catch (Exception $e) {
            return Response::json(['error' => 'Method Invalid'], 400);
        }
    }

    protected function forbiddenResponse()
    {
        return Response::json(['error' => 'Forbidden'], 403);
    }
}
