<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller as BaseController;
use App\Helpers\CustomHelper;
use App\Http\Resources\DeveloperResource;
use App\Http\Resources\PaginatedCollectionResource;
use App\Models\Addon;
use App\Models\OrganizationApiKey;
use App\Models\Setting;
use App\Services\OrganizationApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class DeveloperController extends BaseController
{
    private $organizationApiService;

    public function __construct(OrganizationApiService $organizationApiService)
    {
        $this->organizationApiService = $organizationApiService;
    }

    public function index(){
        $rows = OrganizationApiKey::where('organization_id', session()->get('current_organization'))
            ->where('deleted_at', NULL)
            ->paginate(9);
        $data['rows'] = DeveloperResource::collection($rows);
        $data['title'] = __('API keys');
        $data['url'] = url('/');
        $data['apirequests'] = config('apiguide');
        $data['webhookModule'] = Addon::where('name', 'Webhooks')->where('status', 1)->where('is_active', 1)->exists();
        $webhookAddon = Addon::where('name', 'Webhooks')->where('status', 1)->where('is_active', 1)->exists();
        $data['webhookModule'] = $webhookAddon && CustomHelper::isModuleEnabled('Webhooks');

        return Inertia::render('User/Developer/Index', $data);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'expire_at' => 'nullable|date',
            'limit' => 'nullable|integer|min:1',
        ]);
        $this->organizationApiService->generate($request);

        return Redirect::back()->with(
            'status', [
                'type' => 'success', 
                'message' => __('Your API token has been generated successfully')
            ]
        );
    }

    public function getApiLogs($uuid)
    {
        $token = $this->getOrganizationApiKeyByUuid($uuid);
        if($token instanceof \Illuminate\Http\RedirectResponse) {
            return $token; // Return the redirect response if token not found
        }
        return Inertia::render('User/Developer/Details', [
            'title' => __('API Token Logs'),
            'rows' => PaginatedCollectionResource::collection(DB::table('api_token_logs')
                ->select('api_token_logs.*')
                ->joinWhere('organization_api_keys', 'organization_api_keys.uuid', '=', $token->uuid)
                ->orderBy('created_at', 'desc')
                ->paginate(10)),
            'limit' => $token->limit,
            'expire_at' => $token->expire_at,
            'usage' => $token->usage,
            'name' => $token->name,
        ]);
    }

    public function update(Request $request, $uuid)
    {
        $token = $this->getOrganizationApiKeyByUuid($uuid);
        if($token instanceof \Illuminate\Http\RedirectResponse) {
            return $token; // Return the redirect response if token not found
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'expire_at' => 'nullable|date',
            'limit' => 'nullable|integer|min:1',
        ]);
        if($request->has('name')) $token->name = $request->name;
        if($request->has('limit')) $token->name = $request->limit;
        if($request->has('expire_at')) $token->name = $request->expire_at;

        if($token->save()) {
            return Redirect::back()->with(
                'status', [
                    'type' => 'success',
                    'message' => __('API token updated successfully')
                ]
            );
        } else return Redirect::back()->with(
            'status', [
                'type' => 'error',
                'message' => __('Failed to update API token')
            ]
        );
    }

    public function delete($uuid)
    {
        $this->organizationApiService->destroy($uuid);
    }

    private function getOrganizationApiKeyByUuid($uuid) : \Illuminate\Http\RedirectResponse|\App\Models\OrganizationApiKey
    {
        $token = OrganizationApiKey::where('uuid', $uuid)->first();
        if (!$token) {
            return Redirect::back()->with(
                'status', [
                    'type' => 'error',
                    'message' => __('API token not found')
                ]
            );
        }
        return $token;
    }
}