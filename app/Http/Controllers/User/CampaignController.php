<?php

namespace App\Http\Controllers\User;

use App\Exports\CampaignDetailsExport;
use App\Helpers\DateTimeHelper;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\StoreCampaign;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\CampaignLogResource;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\ContactGroup;
use App\Models\Organization;
use App\Models\Template;
use App\Services\CampaignService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CampaignController extends BaseController
{
    private $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index(Request $request, $uuid = null){
        $organizationId = session()->get('current_organization');
        if($uuid == null){
            $searchTerm = $request->query('search');
            $settings = Organization::where('id', $organizationId)->first();
            $rows = CampaignResource::collection(
                Campaign::with(['template', 'campaignLogs'])
                    ->where('organization_id', $organizationId)
                    ->where('deleted_at', null)
                    ->where(function ($query) use ($searchTerm) {
                        $query->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhereHas('template', function ($templateQuery) use ($searchTerm) {
                                  $templateQuery->where('name', 'like', '%' . $searchTerm . '%');
                              });
                    })
                    ->latest()
                    ->paginate(10)
            );

            return Inertia::render('User/Campaign/Index', [ 'title'=> __('Campaigns'), 'allowCreate' => true, 'rows' => $rows, 'filters' => request()->all(['search']), 'settings' => $settings ]);
        } else if($uuid == 'create'){
            $data['settings'] = Organization::where('id', $organizationId)->first();
            $data['templates'] = \App\Services\TemplateCacheService::getApproved($organizationId);

            $data['contactGroups'] = ContactGroup::where('organization_id', $organizationId)
                ->where('deleted_at', null)
                ->get();

            $data['title'] = __('Create campaign');

            return Inertia::render('User/Campaign/Create', $data);
        } else {
            $data['campaign'] = Campaign::with('contactGroup', 'template')->where('uuid', $uuid)->first();
            if ($data['campaign']) {
                $counts = $data['campaign']->getCounts();
                $data['campaign']['total_message_count'] = $counts->total_message_count ?? 0;
                $data['campaign']['total_sent_count'] = $counts->total_sent_count ?? 0;
                $data['campaign']['total_delivered_count'] = $counts->total_delivered_count ?? 0;
                $data['campaign']['total_failed_count'] = $counts->total_failed_count ?? 0;
                $data['campaign']['total_read_count'] = $counts->total_read_count ?? 0;
            }else{
                $data['campaign']['total_message_count'] = 0;
                $data['campaign']['total_sent_count'] = 0;
                $data['campaign']['total_delivered_count'] = 0;
                $data['campaign']['total_read_count'] = 0;
                $data['campaign']['total_failed_count'] = 0;
            }

            $data['filters'] = request()->all(['search']);

            $searchTerm = $request->query('search');
            $data['rows'] = CampaignLogResource::collection(
                CampaignLog::with('contact', 'chat.logs')
                    ->where('campaign_id', $data['campaign']->id)
                    ->where(function ($query) use ($searchTerm) {
                        $query->whereHas('contact', function ($contactQuery) use ($searchTerm) {
                            $contactQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                         ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                                         ->orWhere('phone', 'like', '%' . $searchTerm . '%');
                        });
                    })
                    ->orderBy('id')
                    ->paginate(10)
            );
            $data['title'] = __('View campaign');

            return Inertia::render('User/Campaign/View', $data);
        }
    }

    public function store(StoreCampaign $request){
        $this->campaignService->store($request);

        return Redirect::route('campaigns')->with(
            'status', [
                'type' => 'success', 
                'message' => __('Campaign created successfully!')
            ]
        );
    }

    public function export($uuid = null){
        return Excel::download(new CampaignDetailsExport($uuid), 'campaign.csv');
    }

    public function delete($uuid){
        $this->campaignService->destroy($uuid);

        return Redirect::back()->with(
            'status', [
                'type' => 'success', 
                'message' => __('Row deleted successfully!')
            ]
        );
    }

    public function rescheduleFailedMessages($campaignUuid)
    {
        $campaign = Campaign::where('uuid', $campaignUuid)->first();
        if($campaign == null) return response()->json([
            'message' => 'Failed to find this campaign',
            'status' => 'error'
        ]);
        $campaign->status = 'ongoing';
        $campaign->save();
        // Fetch all failed campaign logs for the given campaign ID
        DB::table('campaign_logs')->where('campaign_id', $campaign->id)
            ->where('status', 'failed')
            ->update(['status' => 'pending']);

        return Redirect::back()->with(
            'status', [
                'type' => 'success',
                'message' => __('Messages rescheduled successfully!')
            ]
        );
    }
}