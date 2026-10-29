<?php

namespace App\Http\Controllers\User;

use DB;
use App\Http\Controllers\Controller as BaseController;
use App\Helpers\SubscriptionHelper;
use App\Models\Addon;
use App\Models\PaymentGateway;
use App\Models\Subscription;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Services\BillingService;
use App\Services\SubscriptionService;
use App\Services\SubscriptionPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class SubscriptionController extends BaseController
{
    protected $billingService;
    protected $subscriptionService;
    protected $subscriptionPlanService;

    public function __construct()
    {
        $this->billingService = new BillingService();
        $this->subscriptionService = new SubscriptionService();
        $this->subscriptionPlanService = new SubscriptionPlanService();
    }

    public function index(Request $request){
        $organizationId = session()->get('current_organization');
        $data['subscription'] = Subscription::with('plan')->where('organization_id', session()->get('current_organization'))->first();
        $data['taxes'] = TaxRate::where('status', 'active')->where('deleted_at', NULL)->get();
        $data['plans'] = $this->subscriptionPlanService->get($request);
        $data['methods'] = $this->paymentMethods();
        $data['subscriptionDetails'] = SubscriptionService::calculateSubscriptionBillingDetails($organizationId, $data['subscription']->plan_id);
        $data['title'] = __('Billing');

        return Inertia::render('User/Billing/Plan', $data);
    }

    public function store(Request $request){
        $userId = auth()->user()->id;
        $planId = $request->plan;
        $organizationId = session()->get('current_organization');

        $response = SubscriptionService::store($request, $organizationId, $planId, $userId);
        // dd($response);
        if($response){
            if(!isset($response->success)){
                return inertia::location($response);
            } else {
                return Redirect::back()->with(
                    'status', [
                        'type' => 'error', 
                        'message' => $response->error
                    ]
                );
            }
        } else {
            return Redirect::route('user.billing.index')->with(
                'status', [
                    'type' => 'success', 
                    'message' => __('Your subscription has been updated successfully!')
                ]
            );
        }
    }

    public function show($id)
    {
        $organizationId = session()->get('current_organization');

        return Redirect::back()->with('response_data', [
            'data' => SubscriptionService::calculateSubscriptionBillingDetails($organizationId, $id),
        ]);
    }

    public function destroy($id)
    {
        // Your logic for deleting a specific resource
    }

    private function paymentMethods(){
        $mergedData = [];

        // Retrieve active payment methods and add to mergedData
        $paymentMethods = PaymentGateway::where('is_active', 1)->get();
        $mergedData = $paymentMethods->map(function ($method) {
            return ['name' => $method->name];
        })->toArray();

        // Retrieve active addons and check settings
        $activeAddons = Addon::where('category', 'payments')
            ->where('status', 1)
            ->get()
            ->filter(function ($addon) {
                $addonName = strtolower($addon->name);
                $settingKey = "{$addonName}_active";
                return Setting::where('key', $settingKey)->where('value', 1)->exists();
            })
            ->pluck('name')
            ->toArray();

        // Add active addons to mergedData
        foreach ($activeAddons as $addonName) {
            $mergedData[] = ['name' => $addonName];
        }

        return $mergedData;
    }
}