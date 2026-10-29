<?php

namespace App\Http\Controllers\User;

use DB;
use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\StoreAutoReply;
use App\Models\Addon;
use App\Models\AutoReply;
use App\Models\Setting;
use App\Services\AutoReplyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CannedReplyController extends BaseController
{
    private $autoReplyService;

    public function __construct(AutoReplyService $autoReplyService)
    {
        $this->autoReplyService = $autoReplyService;
    }

    public function index(Request $request){
        $rows = $this->autoReplyService->getRows($request);
        $aiAssistantAddon = Addon::where('name', 'AI Assistant')->first();
        $aiAssistantSetting = Setting::where('key', 'ai_assistant')->first();
        $flowBuilderAddon = Addon::where('name', 'Flow builder')->first();
        $flowBuilderSetting = Setting::where('key', 'flow_builder')->first();

        $aimodule = $aiAssistantAddon && $aiAssistantAddon->status && $aiAssistantSetting && $aiAssistantSetting->value == 1;
        $fbmodule = $flowBuilderAddon && $flowBuilderAddon->status && $flowBuilderSetting && $flowBuilderSetting->value == 1;

        return Inertia::render('User/Automation/Basic/Index', [ 
            'title' => __('Canned replies'), 
            'allowCreate' => true, 
            'rows' => $rows, 
            'filters' => request()->all(), 
            'aimodule' => $aimodule,
            'fbmodule' => $fbmodule,
        ]);
    }

    public function create(){
        $data['title'] = __('Canned replies');
        $placeholders = config('formats.placeholders');
        $organizationId = session()->get('current_organization');
        $additionalFields = DB::table('contact_fields')
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->pluck('name');

        $additionalPlaceholders = $additionalFields->map(function($name) {
            // Convert name to lowercase and replace spaces with underscores
            $value = '{' . strtolower(str_replace(' ', '_', $name)) . '}';
            return [
                'value' => $value,
                'label' => $name,
            ];
        })->toArray();

        $data['placeholders'] = array_merge($placeholders, $additionalPlaceholders);

        return Inertia::render('User/Automation/Basic/Create', $data);
    }

    public function store(StoreAutoReply $request){
        $this->autoReplyService->store($request);

        return Redirect::route('cannedReply.create')->with(
            'status', [
                'type' => 'success', 
                'message' => __('Data added successfully!')
            ]
        );
    }

    public function edit($uuid){
        $data['title'] = __('Canned replies');
        $data['autoreply'] = AutoReply::where('uuid', $uuid)->first();
        $placeholders = config('formats.placeholders');
        $organizationId = session()->get('current_organization');
        $additionalFields = DB::table('contact_fields')
            ->where('organization_id', $organizationId)
            ->where('deleted_at', null)
            ->pluck('name');

        $additionalPlaceholders = $additionalFields->map(function($name) {
            // Convert name to lowercase and replace spaces with underscores
            $value = '{' . strtolower(str_replace(' ', '_', $name)) . '}';
            return [
                'value' => $value,
                'label' => $name,
            ];
        })->toArray();

        $data['placeholders'] = array_merge($placeholders, $additionalPlaceholders);

        return Inertia::render('User/Automation/Basic/Edit', $data);
    }

    public function update(StoreAutoReply $request, $uuid){
        $this->autoReplyService->store($request, $uuid);

        return Redirect::route('cannedReply.edit', $uuid)->with(
            'status', [
                'type' => 'success', 
                'message' => __('Data updated successfully!')
            ]
        );
    }

    public function delete($uuid)
    {
        $this->autoReplyService->destroy($uuid);

        return Redirect::back()->with(
            'status', [
                'type' => 'success', 
                'message' => __('Row deleted successfully!')
            ]
        );
    }
}