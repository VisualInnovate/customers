<?php

namespace Modules\IntelliReply\Controllers;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Addon;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Setting;
use Inertia\Inertia;
use Modules\IntelliReply\Models\Document;
use Modules\IntelliReply\Resources\DocumentResource;

class MainController extends BaseController
{
    public function index(Request $request){
        $organizationId = session()->get('current_organization');
        $model = new Document;
        $searchTerm = $request->query('search');

        $data['title'] = __('Settings');
        $data['rows'] = DocumentResource::collection($model->listAll($organizationId, $searchTerm));
        $data['settings'] = Organization::where('id', $organizationId)->first();
        $data['timezones'] = config('formats.timezones');
        $data['countries'] = config('formats.countries');
        $data['sounds'] = config('sounds');
        $data['filters'] = request()->all();

        $aiAssistantAddon = Addon::where('name', 'AI Assistant')->first();
        $aiAssistantSetting = Setting::where('key', 'ai_assistant')->first();

        $data['aimodule'] = $aiAssistantAddon && $aiAssistantAddon->status && $aiAssistantSetting && $aiAssistantSetting->value == 1;

        //dd($data);

        return Inertia::render('IntelliReply::User/Index', $data);
    }

    public function activate(Request $request){
        $organizationId = session()->get('current_organization');
        $organizationConfig = Organization::where('id', $organizationId)->first();

        $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];
        $metadataArray['ai']['active'] = $request->active;

        $updatedMetadataJson = json_encode($metadataArray);
        $organizationConfig->metadata = $updatedMetadataJson;

        if($organizationConfig->save()){
            return back()->with(
                'status', [
                    'type' => 'success', 
                    'message' => $request->active ? __('AI assistant activated') : __('AI assistant deactivated')
                ]
            );
        } else {
            return back()->with(
                'status', [
                    'type' => 'error', 
                    'message' => __('Something went wrong. Refresh the page and try again')
                ]
            );
        }
    }

    public function assistant_setup(Request $request){
        $organizationId = session()->get('current_organization');
        $organizationConfig = Organization::where('id', $organizationId)->first();

        $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];
        $metadataArray['ai']['enable_automatic_responses'] = $request->enable_automatic_responses;
        $metadataArray['ai']['start_keywords'] = $request->start_keywords;
        $metadataArray['ai']['stop_keywords'] = $request->stop_keywords;

        $updatedMetadataJson = json_encode($metadataArray);
        $organizationConfig->metadata = $updatedMetadataJson;

        if($organizationConfig->save()){
            return back()->with(
                'status', [
                    'type' => 'success', 
                    'message' => __('Settings updated successfully')
                ]
            );
        } else {
            return back()->with(
                'status', [
                    'type' => 'error', 
                    'message' => __('Something went wrong. Refresh the page and try again')
                ]
            );
        }
    }

    public function enable_ai_assistant(Request $request, $uuid){
        Contact::where('uuid', $uuid)->update([
            'ai_assistance_enabled' => $request->ai_assistant
        ]);

        return back()->with(
            'status', [
                'type' => 'successs', 
                'message' => __('AI assistant updated successfully!')
            ]
        );
    }

    public function setup(Request $request){
        $organizationId = session()->get('current_organization');
        $organizationConfig = Organization::where('id', $organizationId)->first();

        $metadataArray = $organizationConfig->metadata ? json_decode($organizationConfig->metadata, true) : [];
        $metadataArray['ai']['active'] = $request->active;
        $metadataArray['ai']['platform'] = "OpenAI";
        $metadataArray['ai']['model'] = $request->model;
        $metadataArray['ai']['max_tokens'] = $request->max_tokens;
        $metadataArray['ai']['temperature'] = $request->temperature;
        $metadataArray['ai']['api_key'] = $request->api_key;

        $updatedMetadataJson = json_encode($metadataArray);
        $organizationConfig->metadata = $updatedMetadataJson;

        if($organizationConfig->save()){
            return back()->with(
                'status', [
                    'type' => 'success', 
                    'message' => __('Open AI settings updated successfully')
                ]
            );
        } else {
            return back()->with(
                'status', [
                    'type' => 'error', 
                    'message' => __('Something went wrong. Refresh the page and try again')
                ]
            );
        }
    }
}


