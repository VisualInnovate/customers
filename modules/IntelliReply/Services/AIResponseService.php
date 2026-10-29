<?php

namespace Modules\IntelliReply\Services;

use App\Models\Addon;
use App\Models\Chat;
use App\Models\ChatTicketLog;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Log;
use Modules\IntelliReply\Models\Document;
use OpenAI;

class AIResponseService
{
    public function handleAIResponse($chat, $receivedMessage)
    {
        // Check organization metadata
        $aiAssistantAddon = Addon::where('name', 'AI Assistant')->first();
        $aiAssistantSetting = Setting::where('key', 'ai_assistant')->first();
        // $aimodule = $aiAssistantAddon && $aiAssistantAddon->status && $aiAssistantSetting && $aiAssistantSetting->value == 1;
        $aimodule = (
            $aiAssistantAddon &&
            $aiAssistantAddon->status &&
            $aiAssistantSetting &&
            $aiAssistantSetting->value == 1
        );

        $organizationId = $chat->organization_id;
        $organizationConfig = Organization::find($organizationId);
        $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];
        $is_ai_active = $metadataArray['ai']['active'] ?? false;
        $ai_automatic_responses = $metadataArray['ai']['enable_automatic_responses'] ?? false;
        $chat_ticketing = $metadataArray['tickets']['active'] ?? false;
        $contact = Contact::find($chat->contact_id);
        $ai_assistance_enabled = $contact->ai_assistance_enabled;
        $start_words = $metadataArray['ai']['start_keywords'] ?? "";
        $stop_words = $metadataArray['ai']['stop_keywords'] ?? "";

        Log::info("AI Module: {$aimodule}");

        Log::info("starting ai module");
        if ($aimodule) {
            Log::info("starting ai module inside condition");
            Log::info("start_words: {$start_words}");
            Log::info("stop_words: {$stop_words}");
            // Check if the received message contains any stop words
            if ($stop_words) {
                Log::info("AI stop words");
                $stopWordsArray = array_map('trim', explode(',', $stop_words));
                // Loop through each stop word and check if it exists in the received message
                foreach ($stopWordsArray as $stopWord) {
                    if (stripos($receivedMessage, $stopWord) !== false) {
                        $aiAssistanceEnabled = 0;
                        $contact->update(['ai_assistance_enabled' => $aiAssistanceEnabled]);
                        return false; // Stop further AI processing
                    }
                }
            }

            Log::info("After AI stop words");

            // Check if the received message contains any start words
            if ($start_words) {
                $startWordsArray = array_map('trim', explode(',', $start_words));
                // Loop through each stop word and check if it exists in the received message
                foreach ($startWordsArray as $startWord) {
                    if (stripos($receivedMessage, $startWord) !== false) {
                        $aiAssistanceEnabled = 1;
                        $contact->update(['ai_assistance_enabled' => $aiAssistanceEnabled]);
                        return true;
                    }
                }
            }

            Log::info("AI start words: {$start_words}");


            // Check if chat ticketing is enabled and AI assistance is not already enabled for the contact
            if ($chat_ticketing && !$ai_assistance_enabled) {
                // Retrieve the most recent chat ticket log for the contact
                $chatTicketLog = ChatTicketLog::where('contact_id', $chat->contact_id)
                    ->where(function ($query) {
                        $query->where('description', 'Conversation was opened')
                            ->orWhere('description', 'Conversation was moved from closed to open');
                    })
                    ->orderBy('id', 'desc')
                    ->first();

                // Count the number of outbound chats created after the last chat ticket log
                $outboundChatsCount = Chat::where('contact_id', $chat->contact_id)
                    ->where('created_at', '>', $chatTicketLog->created_at)
                    ->where('type', 'outbound')
                    ->count();

                // Debugging: Log the raw query for deeper inspection
                /*Log::info('Outbound chats query:', [
                    'contact_id' => $chat->contact_id,
                    'created_at >' => $chatTicketLog->created_at,
                    'type' => 'outbound',
                    'query' => Chat::where('contact_id', $chat->contact_id)
                                    ->where('created_at', '>', $chatTicketLog->created_at)
                                    ->where('type', 'outbound')
                                    ->toSql(),
                ]);
                Log::info($chatTicketLog->created_at);
                Log::info("Outbound chats count: {$outboundChatsCount} for contact ID: {$chat->contact_id}");*/

                // If no outbound chats are found, enable AI assistance for the contact
                if ($outboundChatsCount == 0) {
                    $ai_assistance_enabled = 1;
                    Contact::where('id', $chat->contact_id)
                        ->update(['ai_assistance_enabled' => $ai_assistance_enabled]);
                }
            }

            Log::info('AI Response Service: Checking for automatic responses');

            // Check if AI is active and automatic responses are enabled
            if ($is_ai_active && $ai_automatic_responses && $ai_assistance_enabled) {
                $res = $this->chat($organizationId, $receivedMessage);

                Log::info('AI Response: ' . json_encode($res));

                if ($res) {
                    $this->initializeWhatsappService($organizationId)->sendMessage($contact->uuid, $res);
                    return true; // Indicates that an AI response was sent
                }
            }
        }

        return false; // Indicates that no AI response was sent
    }

    public function chat($organizationId, $query)
    {
        $organizationConfig = Organization::where('id', $organizationId)->first();
        $organizationConfig = $organizationConfig ? json_decode($organizationConfig->metadata, true) : [];

        $api_key = $organizationConfig['ai']['api_key'];
        $client = OpenAI::client($api_key);

        // Generate embedding for query
        $response = $client->embeddings()->create([
            'input' => $query,
            'model' => 'text-embedding-ada-002'
        ]);
        $queryEmbedding = $response->embeddings[0]->embedding;

        // Find the closest document
        $documents = Document::where('organization_id', $organizationId)->get();
        $closestDocument = null;
        $closestDistance = PHP_FLOAT_MAX;

        foreach ($documents as $document) {
            $documentEmbeddings = json_decode($document->embeddings);

            foreach ($documentEmbeddings as $documentEmbedding) {
                $distance = $this->cosineSimilarity($queryEmbedding, $documentEmbedding);

                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $closestDocument = $document;
                }
            }
        }

        if ($closestDocument) {
            $documentContent = $closestDocument->content;
            $response = $client->completions()->create([
                'model' => $organizationConfig['ai']['model'],
                'prompt' => "You are customer support service AI Chatbot. You only provide answers that can be strictly found in documentation. If the information is not in the documentation say 'Sorry I don\'t have information about this' documentation:" . $documentContent . "\n\nquestion: " . $query . "\n\nanswer:",
                'max_tokens' => (int) $organizationConfig['ai']['max_tokens'],
                'temperature' => (int) $organizationConfig['ai']['temperature'],
            ]);

            return $response->choices[0]->text;
        }

        return 'Sorry but I don\'t have any information about this.';
    }

    private function cosineSimilarity($vecA, $vecB)
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($vecA); $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        return 1 - ($dotProduct / (sqrt($normA) * sqrt($normB)));
    }

    private function initializeWhatsappService($organizationId)
    {
        $config = Organization::where('id', $organizationId)->first()->metadata;
        $config = $config ? json_decode($config, true) : [];

        $accessToken = $config['whatsapp']['access_token'] ?? null;
        $apiVersion = 'v18.0';
        $appId = $config['whatsapp']['app_id'] ?? null;
        $phoneNumberId = $config['whatsapp']['phone_number_id'] ?? null;
        $wabaId = $config['whatsapp']['waba_id'] ?? null;

        return new WhatsappService($accessToken, $apiVersion, $appId, $phoneNumberId, $wabaId, $organizationId);
    }
}
