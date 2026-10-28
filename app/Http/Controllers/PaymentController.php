<?php

namespace App\Http\Controllers;

use App\Models\BillingPayment;
use App\Models\Organization;
use App\Services\BillingService;
use App\Services\Payment\Gateways\HesabeGateway;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller as BaseController;
use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PaymentController extends BaseController
{
    public function __construct()
    {
        $this->paymentPlatformResolver = new PaymentPlatformResolver();
    }

    public function processPayment(Request $request, $processor)
    {
        $paymentPlatform = $this->paymentPlatformResolver->resolveService($processor);
        session()->put('paymentPlatform', $processor);
        
        if($processor === 'flutterwave'){
            $res = $paymentPlatform->updateSubscription($request->input('transaction_id'));
        }

        return redirect('/billing')->with(
            'status', [
                'type' => $res->success == true ? 'success' : 'error', 
                'message' => $res->success == true ? __('Payment Successful!') : __('Payment Unsuccessful!'), 
            ]
        );
    }

    public function hesabeFallback(Request $request)
    {
        $hesabe = App::make(HesabeGateway::class);
        $decrypted_data = $hesabe->decrypt($request->data);
        $paymentId = $decrypted_data->response->paymentId;
        $amount = $decrypted_data->response->amount;
        $organization = Organization::where('id', $decrypted_data->response->variable1)->firstOrFail();

        // Validate payment
        $existingPayment = BillingPayment::where('details', $paymentId)->first();
        if ($existingPayment) {
            return redirect('/billing')->with('status', [
                'type' => 'error',
                'message' => __('Payment already processed!')
            ]);
        }

        DB::beginTransaction();
        try {
            // Use BillingService to store the payment transaction
            $billingService = new BillingService();
            $billingService->store((object) [
                'uuid' => $organization->uuid,
                'type' => 'payment',
                'amount' => $amount,
                'method' => 'Hesabe',
            ]);

            DB::commit();

            return Redirect::route('user.billing.index')->with(
                'status', [
                    'type' => 'success',
                    'message' => __('Your subscription has been updated successfully!')
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Hesabe payment processing error: ' . $e->getMessage());

            return Redirect::route('user.billing.index')->with(
                'status', [
                    'type' => 'success',
                    'message' => __('An error occurred while processing the payment.')
                ]
            );
        }
    }
}