<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\OrganizationPaymentGateway;
use App\Models\OrganizationPaymentGatewayData;
use App\Http\Requests\StoreOrganizationPaymentGateway;

class OrganizationPaymentGatewayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $OrganizationPaymentGateway = OrganizationPaymentGateway::all();
        return $OrganizationPaymentGateway;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $organization_id = session()->get('current_organization');
        $paymentdata = OrganizationPaymentGatewayData::where('organization_gateway_id', $id)
        ->where('organization_id', $organization_id)->first();
        if (empty($paymentdata)) {
            return response()->json(['success' => false, 'data'=> []]);
        }
        return response()->json(['success' => true, 'data'=> $paymentdata]);
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(StoreOrganizationPaymentGateway $request, $type){
        if (env('APP_ENV') === 'demo') {
            // Return a response indicating that the function is not allowed in demo environment
            return Redirect::back()->with(
                'status', [
                    'type' => 'error', 
                    'message' => __('Updating settings is not allowed in demo.')
                ]
            );
        }

        $metadata = [];

        switch (strtolower($type)) {
            case 'MyFatorah':
                $metadata = [
                    'api_key' => $request->api_key,
                    'secret_key' => $request->secret_key,
                    'merchant_id' => $request->merchant_id
                ];
                break;
                
            case 'Hesabe':
                $metadata = [
                    'merchant_id' => $request->merchant_id,
                    'access_key' => $request->access_key,
                    'secret_key' => $request->secret_key,
                    'iv_key' => $request->iv_key
                ];
                break;

            case 'tap':
                $metadata = [
                    'secret_key' => $request->secret_key,
                    'publishale_key' => $request->publishale_key,
                ];
                break;
                
        }
        $payment = OrganizationPaymentGateway::where('name', '=', $type)->first();
        $method = OrganizationPaymentGatewayData::where('organization_gateway_id', '=', $payment->id)
        ->where('organization_id', '=', Auth::user()->organization_id)
        ->update([
            'metadata' => $metadata,
            'active' => $request->active
        ]);

        return redirect('/organization-payment-gateways')->with(
            'status', [
                'type' => 'success', 
                'message' => ucfirst($type) . ' updated successfully!'
            ]
        );
    }
}


