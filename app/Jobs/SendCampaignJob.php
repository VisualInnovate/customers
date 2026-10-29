<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\WhatsappService;
use App\Traits\HasUuid;
use App\Traits\TemplateTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, TemplateTrait, SerializesModels;

    private $organizationId;
    private $whatsappService;

    public function handle()
    {
        Log::info('Starting SendCampaignJob execution');

        try {
            $campaigns = Campaign::whereIn('status', ['scheduled', 'ongoing'])
                ->with('organization')
                ->whereNull('deleted_at')
                ->cursor();

            Log::info('Fetched campaigns for processing', [
                'campaign_count' => $campaigns->count()
            ]);

            $campaigns->each(function ($campaign) {
                Log::info('Processing campaign', [
                    'campaign_id' => $campaign->id,
                    'campaign_status' => $campaign->status,
                    'organization_id' => $campaign->organization_id
                ]);

                $organization = $campaign->organization;
                $timezone = 'UTC';

                if ($organization) {
                    $metadata = $organization->metadata;
                    $metadata = isset($metadata) ? json_decode($metadata, true) : null;

                    if ($metadata && isset($metadata['timezone'])) {
                        $timezone = $metadata['timezone'];
                    }
                }

                Log::info('Using timezone for campaign', [
                    'campaign_id' => $campaign->id,
                    'timezone' => $timezone
                ]);

                $scheduledAt = Carbon::parse($campaign->scheduled_at, 'UTC')->timezone($timezone);

                if ($scheduledAt->lte(Carbon::now($timezone))) {
                    $this->processCampaign($campaign);
                } else {
                    Log::info('Campaign scheduled for future execution', [
                        'campaign_id' => $campaign->id,
                        'scheduled_at' => $scheduledAt->toIso8601String()
                    ]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error in campaign job execution', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function processCampaign(Campaign $campaign)
    {
        Log::info('Processing campaign', [
            'campaign_id' => $campaign->id,
            'status' => $campaign->status
        ]);

        if ($campaign->status === 'scheduled') {
            $this->processPendingCampaign($campaign);
        } elseif ($campaign->status === 'ongoing') {
            $this->sendOngoingCampaignMessages($campaign);
        }
    }

    protected function processPendingCampaign(Campaign $campaign)
    {
        Log::info('Processing pending campaign', [
            'campaign_id' => $campaign->id
        ]);

        $contacts = $this->getContactsForCampaign($campaign);

        Log::info('Retrieved contacts for campaign', [
            'campaign_id' => $campaign->id,
            'contacts_count' => count($contacts)
        ]);

        if ($this->createCampaignLogs($campaign, $contacts)) {
            Log::info('Successfully created campaign logs', [
                'campaign_id' => $campaign->id
            ]);

            if ($this->updateCampaignStatus($campaign, 'ongoing')) {
                Log::info('Updated campaign status to ongoing', [
                    'campaign_id' => $campaign->id
                ]);

                $campaign = Campaign::find($campaign->id);
                $this->processCampaign($campaign);
            }
        } else {
            Log::warning('Failed to create campaign logs', [
                'campaign_id' => $campaign->id
            ]);
        }
    }

    protected function getContactsForCampaign(Campaign $campaign)
    {
        Log::info('Fetching contacts for campaign', [
            'campaign_id' => $campaign->id,
            'contact_group_id' => $campaign->contact_group_id
        ]);

        $contacts = (is_null($campaign->contact_group_id) || empty($campaign->contact_group_id) || $campaign->contact_group_id === '0')
            ? Contact::where('organization_id', $campaign->organization_id)->whereNull('deleted_at')->get()
            : optional($campaign->contactGroup)->contacts ?? collect();

        Log::info('Retrieved contacts', [
            'campaign_id' => $campaign->id,
            'contacts_count' => $contacts->count()
        ]);

        return $contacts;
    }

    protected function createCampaignLogs(Campaign $campaign, $contacts)
    {
        Log::info('Creating campaign logs', [
            'campaign_id' => $campaign->id,
            'total_contacts' => $contacts->count()
        ]);

        $campaignLogs = [];
        $contactIds = $contacts->pluck('id');

        $existingLogs = CampaignLog::where('campaign_id', $campaign->id)
            ->whereIn('contact_id', $contactIds)
            ->pluck('contact_id')
            ->toArray();

        Log::info('Found existing campaign logs', [
            'campaign_id' => $campaign->id,
            'existing_logs_count' => count($existingLogs)
        ]);

        $newContacts = $contactIds->diff($existingLogs);

        $campaignLogs = $newContacts->map(function ($contactId) use ($campaign) {
            return [
                'campaign_id' => $campaign->id,
                'contact_id' => $contactId,
                'created_at' => now(),
            ];
        })->toArray();

        if (!empty($campaignLogs)) {
            Log::info('Inserting new campaign logs', [
                'campaign_id' => $campaign->id,
                'new_logs_count' => count($campaignLogs)
            ]);
            return CampaignLog::insert($campaignLogs);
        }

        Log::info('No new campaign logs to create', [
            'campaign_id' => $campaign->id
        ]);
        return false;
    }

    protected function updateCampaignStatus(Campaign $campaign, $status)
    {
        Log::info('Updating campaign status', [
            'campaign_id' => $campaign->id,
            'old_status' => $campaign->status,
            'new_status' => $status
        ]);

        $result = Campaign::where('uuid', $campaign->uuid)->update(['status' => $status]);

        if ($result) {
            Log::info('Successfully updated campaign status', [
                'campaign_id' => $campaign->id,
                'status' => $status
            ]);
        } else {
            Log::warning('Failed to update campaign status', [
                'campaign_id' => $campaign->id,
                'status' => $status
            ]);
        }

        return $result;
    }

    protected function sendOngoingCampaignMessages(Campaign $campaign)
    {
        Log::info('Processing ongoing campaign messages', [
            'campaign_id' => $campaign->id
        ]);

        $this->processPendingCampaignLogs($campaign);

        if (!$this->hasPendingCampaignLogs($campaign)) {
            Log::info('No pending logs found, marking campaign as completed', [
                'campaign_id' => $campaign->id
            ]);
            $this->updateCampaignStatus($campaign, 'completed');
        }
    }

    protected function processPendingCampaignLogs(Campaign $campaign)
    {
        Log::info('Processing pending campaign logs', [
            'campaign_id' => $campaign->id
        ]);

        CampaignLog::with('campaign', 'contact')
            ->where('campaign_id', $campaign->id)
            ->where('status', '=', 'pending')
            ->chunk(500, function ($pendingCampaignLogs) use ($campaign) {
                Log::info('Processing chunk of pending campaign logs', [
                    'campaign_id' => $campaign->id,
                    'chunk_size' => count($pendingCampaignLogs)
                ]);

                foreach ($pendingCampaignLogs as $campaignLog) {
                    if ($campaignLog->status === 'ongoing' || $campaignLog->status === 'success') {
                        Log::info('Skipping already processed campaign log', [
                            'campaign_log_id' => $campaignLog->id,
                            'status' => $campaignLog->status
                        ]);
                        continue;
                    }
                    $this->sendTemplateMessage($campaignLog);
                }
            });
    }

    protected function hasPendingCampaignLogs(Campaign $campaign)
    {
        $hasPending = CampaignLog::where('status', 'pending')
            ->where('campaign_id', $campaign->id)
            ->exists();

        Log::info('Checked for pending campaign logs', [
            'campaign_id' => $campaign->id,
            'has_pending_logs' => $hasPending
        ]);

        return $hasPending;
    }

    protected function sendTemplateMessage(CampaignLog $campaignLog)
    {
        DB::transaction(function () use ($campaignLog) {
            Log::info('Starting template message send', [
                'campaign_log_id' => $campaignLog->id,
                'campaign_id' => $campaignLog->campaign_id,
                'contact_id' => $campaignLog->contact_id
            ]);

            $log = CampaignLog::where('id', $campaignLog->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($log) {
                try {
                    $log->status = 'ongoing';
                    $log->save();

                    $this->organizationId = $campaignLog->campaign->organization_id;
                    $this->initializeWhatsappService();

                    $template = $this->buildTemplateRequest($campaignLog->campaign_id, $campaignLog->contact);

                    Log::info('Sending template message', [
                        'campaign_log_id' => $campaignLog->id,
                        'template' => $template,
                        'contact_uuid' => $campaignLog->contact->uuid
                    ]);

                    $responseObject = $this->whatsappService->sendTemplateMessage(
                        $campaignLog->contact->uuid,
                        $template,
                        $campaignLog->campaign_id
                    );

                    $this->updateCampaignLogStatus($campaignLog, $responseObject);
                } catch (\Exception $e) {
                    Log::error('Failed to send template message', [
                        'campaign_log_id' => $campaignLog->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'organization_id' => $this->organizationId,
                        'contact_uuid' => $campaignLog->contact->uuid ?? null
                    ]);

                    $log->status = 'failed';
                    $log->metadata = json_encode([
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'timestamp' => now()->toIso8601String()
                    ]);
                    $log->updated_at = now();
                    $log->save();

                    throw $e;
                }
            } else {
                Log::warning('Campaign log not found or not in pending status', [
                    'campaign_log_id' => $campaignLog->id,
                    'current_status' => $campaignLog->status
                ]);
            }
        });
    }

    protected function updateCampaignLogStatus(CampaignLog $campaignLog, $responseObject)
    {
        Log::info('Updating campaign log status', [
            'campaign_log_id' => $campaignLog->id
        ]);

        $log = CampaignLog::find($campaignLog->id);
        $isSuccess = ($responseObject->success === true);

        $metadata = clone $responseObject;
        if (isset($metadata->data) && is_object($metadata->data)) {
            $metadata->data = clone $metadata->data;
        }
        unset($metadata->success);
        if (property_exists($metadata, 'data') && property_exists($metadata->data, 'chat')) {
            unset($metadata->data->chat);
        }

        if (!$isSuccess) {
            $errorDetails = [
                'error_timestamp' => now()->toIso8601String(),
                'error_details' => $responseObject->error ?? null,
                'error_code' => $responseObject->error_code ?? null,
                'http_status' => $responseObject->status ?? null,
            ];
            $metadata->error_details = $errorDetails;

            Log::error('Template message send failed', [
                'campaign_log_id' => $campaignLog->id,
                'campaign_id' => $campaignLog->campaign_id,
                'contact_id' => $campaignLog->contact_id,
                'error_details' => $errorDetails,
                'organization_id' => $this->organizationId,
                'response' => $responseObject
            ]);
        } else {
            Log::info('Template message sent successfully', [
                'campaign_log_id' => $campaignLog->id,
                'campaign_id' => $campaignLog->campaign_id,
                'contact_id' => $campaignLog->contact_id,
                'chat_id' => $responseObject->data->chat->id ?? null
            ]);
        }

        $log->chat_id = $responseObject->data->chat->id ?? null;
        $log->status = $isSuccess ? 'success' : 'failed';
        $log->metadata = json_encode($metadata);
        $log->updated_at = now();
        $log->save();
    }

    private function initializeWhatsappService()
    {
        Log::info('Initializing WhatsApp service', [
            'organization_id' => $this->organizationId
        ]);

        $organization = Organization::where('id', $this->organizationId)->first();
        $config = $organization->metadata ? json_decode($organization->metadata, true) : [];

        $accessToken = $config['whatsapp']['access_token'] ?? null;
        $apiVersion = 'v18.0';
        $appId = $config['whatsapp']['app_id'] ?? null;
        $phoneNumberId = $config['whatsapp']['phone_number_id'] ?? null;
        $wabaId = $config['whatsapp']['waba_id'] ?? null;

        if (!$accessToken || !$appId || !$phoneNumberId || !$wabaId) {
            Log::warning('Missing WhatsApp configuration', [
                'organization_id' => $this->organizationId,
                'has_access_token' => (bool)$accessToken,
                'has_app_id' => (bool)$appId,
                'has_phone_number_id' => (bool)$phoneNumberId,
                'has_waba_id' => (bool)$wabaId
            ]);
        }

        $this->whatsappService = new WhatsappService($accessToken, $apiVersion, $appId, $phoneNumberId, $wabaId, $this->organizationId);

        Log::info('WhatsApp service initialized', [
            'organization_id' => $this->organizationId,
            'api_version' => $apiVersion
        ]);
    }
}
