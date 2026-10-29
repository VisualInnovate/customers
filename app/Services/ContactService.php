<?php

namespace App\Services;

use App\Helpers\WebhookHelper;
use App\Models\Chat;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\ContactTag;
use App\Models\Organization;
use App\Models\Setting;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Propaganistas\LaravelPhone\PhoneNumber;

class ContactService
{
    private $organizationId;
    private $apiVersion;
    private $accessToken;

    public function __construct($organizationId)
    {
        $this->organizationId = $organizationId;
        $this->apiVersion = env('GRAPH_API_VERSION', 'v20.0');
        $config = Organization::where('id', $this->organizationId)->first()->metadata;
        $config = $config ? json_decode($config, true) : [];
        $this->accessToken = $config['whatsapp']['access_token']  ?? null;
    }

    public function setHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }

    
    public function store(object $request, $uuid = null){
        // if ($uuid === null) {
        //     $url = "https://graph.facebook.com/v1/contacts";
        //     $client = new Client();
        //     $response = $client->post($url, [
        //         'json' => [
        //             'blocking' => 'no_wait',
        //             'contacts' => [$request->phone],
        //             'force_check' => true,
        //         ],
        //         // 'headers' => $this->setHeaders(),
        //     ]);

        //     dd($response);

        //     if ($response->successful()) {
        //         $data = $response->json();            
        //         $checkContact = $data['contacts'][0] ?? null;
        //         if ($checkContact && $checkContact['status'] === 'valid') {
        //             $contact = new Contact();
        //         }
        //         return [
        //             'valid' => false,
        //             'status' => $contact['status'] ?? 'unknown',
        //         ];
        //         dd($data);
        //     }
            
        // } else {
        //     $contact = Contact::where('uuid', $uuid)->firstOrFail();
        // }
        $contact = $uuid === null ? new Contact() : Contact::where('uuid', $uuid)->firstOrFail();
        $contact->first_name = $request->first_name;
        $contact->last_name = $request->last_name;
        $contact->email = $request->email;

        $phone = new PhoneNumber($request->phone);
        $contact->phone = $phone->formatE164();//$phone->formatInternational();

        if($request->group){
            $contactGroup = ContactGroup::where('uuid', $request->group)->first();
            $contact->contact_group_id = $contactGroup->id ?? null;
        }

        if($request->hasFile('file')){
            $storage = Setting::where('key', 'storage_system')->first()->value;
            $fileName = $request->file('file')->getClientOriginalName();
            $fileContent = $request->file('file');

            if($storage === 'local'){
                $file = Storage::disk('local')->put('public', $fileContent);
                $mediaFilePath = $file;

                $contact->avatar = '/media/' . ltrim($mediaFilePath, '/');
            } else if($storage === 'aws') {
                $file = $request->file('file');
                $uploadedFile = $file->store('uploads/media/contacts/' . $this->organizationId, 's3');
                $mediaFilePath = Storage::disk('s3')->url($uploadedFile);

                $contact->avatar = $mediaFilePath;
            }
        }

        if($uuid === null){
            $contact->organization_id = $this->organizationId;
            $contact->created_by = auth()->user() ? auth()->user()->id : 0;
            $contact->created_at = now();
        }

        $address = json_encode([
            'street' => $request->street,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $request->country,
        ]);
        
        $contact->address = $address;
        $contact->metadata = json_encode($request->metadata);
        $contact->updated_at = now();
        $contact->save();

        if($uuid === null && isset($request->tags))
        {
            $request->validate(['tags' => 'array','tags.*' => 'integer|exists:tags,id']);
            $organizationId = $this->organizationId; 
            
            $contact->tags()->syncWithoutDetaching(
                collect($request->tags)
                    ->mapWithKeys(fn($tagId) => [
                        $tagId => [
                            'organization_id' => $organizationId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ])
            );
        }

        if ($request->has('tags') && $uuid !== null) {
            $request->validate(['tags' => 'array','tags.*' => 'integer|exists:tags,id']);
            $organizationId = $this->organizationId;
            $contact->tags()->sync(
                collect($request->tags)
                    ->mapWithKeys(fn($tagId) => [
                        $tagId => [
                            'organization_id' => $organizationId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ])
            );
        }

        // Prepare a clean contact object for webhook
        $cleanContact = $contact->makeHidden(['id', 'organization_id', 'created_by']);

        // Trigger webhook
        WebhookHelper::triggerWebhookEvent($uuid === null ? 'contact.created' : 'contact.updated', $cleanContact, $this->organizationId);

        return $contact;
    }

    public function favorite(object $request, $uuid){
        $contact = Contact::where('uuid', $uuid)->firstOrFail();
        $contact->is_favorite = $request->favorite;
        $contact->updated_at = date('Y-m-d H:i:s');
        $contact->save();
    }

    public function delete($uuids){
        $deletedContacts = [];

        if (empty($uuids)) {
            // Delete all contacts (soft delete)
            $contacts = Contact::where('organization_id', $this->organizationId)->get();
            Contact::whereNotNull('id')->where('organization_id', $this->organizationId)->delete();

            // Prepare deleted contacts for the webhook
            foreach ($contacts as $contact) {
                $deletedContacts[] = [
                    'uuid' => $contact->uuid,
                    'deleted_at' => now()->toISOString(), // Assuming you're using Laravel's Carbon
                ];
            }

            //Mark all unread chats as read
            Chat::where('organization_id', $this->organizationId)
                ->where('type', 'inbound')
                ->whereNull('deleted_at')
                ->where('is_read', 0)
                ->update([
                    'is_read' => 1
                ]);

            return response()->json([
                'success' => false,
                'message' => 'uuids array is empty, no contacts found'
            ]);
            
        } else {
            // Delete contacts by UUIDs (soft delete)
            foreach($uuids as $uuid){
                $contact = Contact::where('uuid', $uuid)->firstOrFail();

                // Prepare deleted contact for the webhook
                $deletedContacts[] = [
                    'uuid' => $contact->uuid,
                    'deleted_at' => now()->toISOString(),
                ];

                //Mark all unread chats as read
                Chat::where('contact_id', $contact->id)
                    ->where('type', 'inbound')
                    ->whereNull('deleted_at')
                    ->where('is_read', 0)
                    ->update([
                        'is_read' => 1
                    ]);
            }

            Contact::whereIn('uuid', $uuids)->where('organization_id', $this->organizationId)->delete();
        }

        // Trigger webhook with deleted contacts
        WebhookHelper::triggerWebhookEvent('contact.deleted', [
            'list' => $deletedContacts
        ], $this->organizationId);
    }
}