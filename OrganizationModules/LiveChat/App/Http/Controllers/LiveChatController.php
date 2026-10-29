<?php

namespace OrganizationModules\LiveChat\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\LiveChatResource;
use App\Models\Organization;
use App\Models\OrganizationIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LiveChatController extends Controller
{

    public function index()
    {
        $organizationId = session()->get('current_organization');
        $integration = OrganizationIntegration::where('organization_id', $organizationId)->where('name', 'LiveChat')->get();

        // dd($integration->toArray());
        return Inertia::render('User/LiveChat/Show', $integration);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'live_chat' => [
                'required', 
                'string',
                // 'regex:/^([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\.-]*)*\/?$/i'
            ],
        ]);
    
        $organization = Organization::find(session()->get('current_organization'));

        $OrganizationIntegration = OrganizationIntegration::where('organization_id', $organization->id)->where('name', 'LiveChat')->first();
        if ($OrganizationIntegration) {
            $OrganizationIntegration->update([
                'metadata' => [
                    'api_key' => 'chat_sk_'.$organization->identifier,
                    'domain' => $request->live_chat
                ],
            ]);
            
        }else {
            $OrganizationIntegration = OrganizationIntegration::create([
                'organization_id' => $organization->id,
                'name' => 'LiveChat',
                'metadata' => [
                    'api_key' => 'chat_sk_'.$organization->identifier,
                    'domain' => $request->live_chat
                ],
                'active' => true,
            ]);
        }
    
        return Redirect::route('integrations')->with(
            'status', [
                'type' => 'success', 
                'message' => __('Live Chat configured successfully!')
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
