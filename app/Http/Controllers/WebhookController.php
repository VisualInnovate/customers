<?php

namespace App\Http\Controllers;

use App\Events\NewChatEvent;
use App\Helpers\WebhookHelper;
use App\Http\Controllers\Controller as BaseController;
use App\Jobs\ProcessWebhookedMessages;
use App\Jobs\SmbSyncContacts;
use App\Models\AutoReply;
use App\Models\Call;
use App\Models\Contact;
use App\Models\Chat;
use App\Models\ChatLog;
use App\Models\ChatStatusLog;
use App\Models\ChatMedia;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\Template;
use App\Resolvers\PaymentPlatformResolver;
use App\Services\AutoReplyService;
use App\Services\ChatService;
use App\Services\StripeService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Propaganistas\LaravelPhone\PhoneNumber;

class WebhookController extends BaseController
{
    protected $paymentPlatformResolver;

    public function __construct()
    {
        $this->paymentPlatformResolver = new PaymentPlatformResolver();

        Config::set('broadcasting.connections.pusher', [
            'driver' => 'pusher',
            'key' => Setting::where('key', 'pusher_app_key')->value('value'),
            'secret' => Setting::where('key', 'pusher_app_secret')->value('value'),
            'app_id' => Setting::where('key', 'pusher_app_id')->value('value'),
            'options' => [
                'cluster' => Setting::where('key', 'pusher_app_cluster')->value('value'),
            ],
        ]);
    }

    public function whatsappWebhook(Request $request){
        $verifyToken = Setting::where('key', 'whatsapp_callback_token')->first()->value;

        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return Response::make($challenge, 200);
        } else {
            return Response::json(['error' => 'Forbidden'], 200);
        }
    }

    public function handle(Request $request, $identifier = null)
    {
        $organization = $this->getOrganizationByIdentifier($identifier);

        if (!$organization) {
            return $this->forbiddenResponse();
        }
        if(!isset($request->entry)) {
            Log::info('Webhook called without entry data for org: ' . $organization->id);
            return $this->whatsappWebhook($request);
        }
        return $this->handleMethod($request, $organization);
    }

    protected function getOrganizationByIdentifier($identifier)
    {
        return Organization::where('identifier', $identifier)->first();
    }

    protected function handleMethod(Request $request, Organization $organization)
    {
        if ($request->isMethod('get')) {
            return $this->handleGetRequest($request, $organization);
        } elseif ($request->isMethod('post')) {
            $metadata = json_decode($organization->metadata);

            if (empty($metadata)) {
                return $this->forbiddenResponse();
            }

            /*$appSecret = $metadata->whatsapp->app_secret;
            $headerSignature = $request->header('X-Hub-Signature-256');
            $payload = $request->getContent();
            $calculatedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

            if (!$this->isValidSignature($calculatedSignature, $headerSignature)) {
                return $this->invalidSignatureResponse();
            }*/

            return $this->handlePostRequest($request, $organization);
        }

        return Response::json(['error' => 'Method Not Allowed'], 405);
    }

    protected function forbiddenResponse()
    {
        return Response::json(['error' => 'Forbidden'], 403);
    }

    protected function isValidSignature($calculatedSignature, $headerSignature)
    {
        return hash_equals($calculatedSignature, $headerSignature);
    }

    protected function invalidSignatureResponse()
    {
        return Response::json(['status' => 'error', 'message' => __('Invalid payload signature')], 400);
    }

    protected function handleGetRequest(Request $request, Organization $organization)
    {
        try {
            $verifyToken = $organization->identifier;

            $mode = $request->input('hub_mode');
            $token = $request->input('hub_verify_token');
            $challenge = $request->input('hub_challenge');

            if ($mode === 'subscribe' && $token === $verifyToken) {
                return Response::make($challenge, 200);
            } else {
                return Response::json(['error' => 'Forbidden'], 404);
            }
        } catch (\Exception $e) {
            Log::error("Error processing webhook: " . $e->getMessage());
            return Response::json(['error' => $e->getMessage()], 403);
        }
    }

    protected function handlePostRequest(Request $request, Organization $organization)
    {
        $res = $request->entry[0]['changes'][0];
        if($res['field'] != 'history')
            $this->forwardToStaging($request->getContent(), $organization->identifier);

        if($res['field'] === 'messages') {
            // Real-time incoming messages - MESSAGES QUEUE (highest priority)
            ProcessWebhookedMessages::dispatch($res, $organization)
                ->onQueue('messages');
        } else if($res['field'] === 'message_template_status_update'){
            $response = $res['value'] ?? null;
            $template = Template::where('meta_id', $response['message_template_id'])->first();

            if($template){
                $template->status = $response['event'];
                $template->save();
            }
        } else if($res['field'] === 'account_review_update'){
            //Account Status
            $response = $res['value'] ?? null;
            $organizationConfig = Organization::where('id', $organization->id)->first();
            $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];

            $metadataArray['whatsapp']['account_review_status'] = $response['decision'] ?? NULL;

            $updatedMetadataJson = json_encode($metadataArray);
            $organizationConfig->metadata = $updatedMetadataJson;
            $organizationConfig->save();
        } else if($res['field'] === 'phone_number_name_update'){
            //Display Name
            $response = $res['value'] ?? null;

            if($response['decision'] == 'APPROVED'){
                $organizationConfig = Organization::where('id', $organization->id)->first();
                $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];

                $metadataArray['whatsapp']['verified_name'] = $response['requested_verified_name'] ?? NULL;

                $updatedMetadataJson = json_encode($metadataArray);
                $organizationConfig->metadata = $updatedMetadataJson;
                $organizationConfig->save();
            }
        } else if($res['field'] === 'phone_number_quality_update'){
            $response = $res['value'] ?? null;
            $organizationConfig = Organization::where('id', $organization->id)->first();
            $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];

            $metadataArray['whatsapp']['messaging_limit_tier'] = $response['current_limit'] ?? NULL;

            $updatedMetadataJson = json_encode($metadataArray);
            $organizationConfig->metadata = $updatedMetadataJson;
            $organizationConfig->save();
        } else if($res['field'] === 'business_capability_update'){
            //messaging_tier_limit
            $response = $res['value'] ?? null;
            $organizationConfig = Organization::where('id', $organization->id)->first();
            $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];

            $metadataArray['whatsapp']['max_daily_conversation_per_phone'] = $response['max_daily_conversation_per_phone'] ?? NULL;
            $metadataArray['whatsapp']['max_phone_numbers_per_business'] = $response['max_phone_numbers_per_business'] ?? NULL;

            $updatedMetadataJson = json_encode($metadataArray);
            $organizationConfig->metadata = $updatedMetadataJson;
            $organizationConfig->save();
        } else if ($res['field'] === 'smb_app_state_sync') {
            Log::info("Processing SMB App State Sync for organization: " . $organization->id);
            Log::info("Processing SMB App State Sync with data: " . json_encode($res));
            $contacts = $res['value']['state_sync'] ?? [];
            try {
                foreach (array_chunk($contacts, 100) as $chunk) {
                    SmbSyncContacts::dispatchAfterResponse($organization->id, $chunk);
                }
                return Response::json(['status' => 'success', 'message' => 'Contacts migrated successfully'], 200);
            } catch (\Exception $e) {
                Log::error("Error processing SMB App State Sync: " . $e->getMessage());
                return Response::json(['error' => 'Failed to process SMB App State Sync'], 500);
            }
        } else if($res['field'] === 'history') {
            Log::info('history fetched update: ' . $request->getContent());
            try {
                if (isset($res['value']['history'])) {
                    foreach ($res['value']['history'] as $historyChunk) {
                        if (!isset($historyChunk['threads'])) {
                            continue;
                        }

                        foreach ($historyChunk['threads'] as $thread) {
                            if (!isset($thread['messages'])) {
                                continue;
                            }

                            // Map each history message to the format ProcessWebhookedMessages::dispatch() expects
                            $mappedRes = [
                                'value' => [
                                    'contacts' => $res['value']['contacts'] ?? [],
                                    'messages' => $thread['messages']
                                ]
                            ];

                            // Pass to existing messages handler - HISTORY QUEUE (lowest priority)
                            ProcessWebhookedMessages::dispatch($mappedRes, $organization, true, $thread['id'])
                                ->onQueue('history')
                                ->afterResponse();
                        }
                    }
                } else if(isset($res['value']['message_echoes'])) {
                    // Handle message echoes for media description - HISTORY QUEUE
                    $res['value']['messages'] = $res['value']['message_echoes'];
                    ProcessWebhookedMessages::dispatch($res, $organization, true)
                        ->onQueue('history')
                        ->afterResponse();
                }

            } catch(\Exception $e) {
                Log::error("Error processing history webhook: " . $e->getMessage(), $e->getTrace());
                return Response::json(['error' => 'Failed to process history webhook'], 500);
            }

            return Response::json(['status' => 'success']);
        } else if($res['field'] == 'smb_message_echoes') {
            $res['value']['messages'] = $res['value']['message_echoes'] ?? [];
            try {
                ProcessWebhookedMessages::dispatch($res, $organization, false, null, true)
                    ->onQueue('default')
                    ->afterResponse();

            } catch (\Exception $e) {
                Log::error("Error processing smb_message_echoes: " . $e->getMessage(), $e->getTrace());
            }
        } else if($res['field'] == 'calls') {
            foreach (($res['value']['calls'] ?? []) as $call) {
                // store the call with the corresponding organization and send pusher notification for the frontend
                if($call['event'] == 'connect') { // create a new call
                    $existingCall = Call::where('call_id', $call['id'])->first();
                    if(!$existingCall) {
                        $contact_number = sanitize_phone($call['from'])->formatE164();
                        $contact = Contact::firstOrCreate(
                            ['organization_id' => $organization->id, 'phone' => $contact_number],
                            ['name' => $contact_number, 'deleted_at' => null]
                        );
                        $newCall = new Call();
                        $newCall->call_id = $call['id'];
                        $newCall->organization_id = $organization->id;
                        $newCall->contact_id = $contact->id;
                        $newCall->from = $call['from'];
                        $newCall->to = $call['to'];
                        $newCall->status = 'initiated';
                        $newCall->direction = 'inbound';
                        $newCall->session_id = null;
                        $newCall->start_time = isset($call['timestamp']) ? Carbon::createFromTimestamp($call['timestamp']) : null;
                        $newCall->metadata = $call['connection']['webrtc'] ?? null;
                        $newCall->save();

                        // Broadcast the new call event
                        try {
                            event(new \App\Events\IncomingCallEvent($newCall));
                        } catch (Exception $e) {
                            Log::error("Failed to broadcast NewCallEvent: " . $e->getMessage());
                        }
                    }
                } elseif ($call['event'] == 'terminate') {
                    $existingCall = Call::where('call_id', $call['id'])->first();
                    if($existingCall) {
                        $existingCall->status = strtolower($call['status']);
                        $existingCall->end_time = Carbon::createFromTimestamp($call['end_time'] ?? now());
                        $existingCall->duration = $call['duration'] ?? $existingCall->end_time - $existingCall->start_time;
                        $existingCall->save();

                        // Broadcast the updated call event
                        try {
                            event(new \App\Events\UpdateCallEvent($existingCall));
                        } catch (Exception $e) {
                            Log::error("Failed to broadcast UpdateCallEvent: " . $e->getMessage());
                        }
                    }
                } else {
                    Log::alert('Unknown call event: ' . json_encode($call));
                }
            }
        } else {
            Log::info('Unknown field in webhook: ' . json_encode($res));
        }
        return Response::json(['status' => 'success'], 200);
    }

    public function processWebhook(Request $request, $processor)
    {
        $paymentPlatform = $this->paymentPlatformResolver->resolveService($processor);
        session()->put('paymentPlatform', $processor);

        return $paymentPlatform->handleWebhook($request);
    }

    function forwardToStaging(string $rawBody, $identifier): void // relay to forward webhook to the staging environment
    {
        $staging_url = config('app.staging_url');
        if($staging_url == null || $staging_url == '') return;

        $stagingUrl = $staging_url . '/webhook/whatsapp/' . $identifier;

        // Escape payload properly for shell use
        $escapedBody = escapeshellarg($rawBody);
        $escapedUrl = escapeshellarg($stagingUrl);

        // Run curl in background, suppress output
        $cmd = "curl -X POST -H 'Content-Type: application/json' -d $escapedBody $escapedUrl > /dev/null 2>&1 &";
        exec($cmd);
    }

}