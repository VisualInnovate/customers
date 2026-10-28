<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payment\Gateways\Gateway;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class PaymentService
{
    private Gateway $gateway;
    public function __construct(string $gateway) {
        $payment_gateway = PaymentGateway::where('name', $gateway)->first();
        if(!$payment_gateway) abort(404, 'Payment Gateway Not Found');
        $handle = $payment_gateway->metadata['handle'] ?? Str::slug($payment_gateway->name);
        $class = config("payment.$handle.class");
        if($class == null) $class = config('payment.default');
        $gatewayInstance = App::make($class);

        if (! $gatewayInstance instanceof Gateway) {
            throw new \InvalidArgumentException("Gateway [$class] must implement the Gateway interface.");
        }

        $this->gateway = $gatewayInstance;
    }

    public function checkout(User $user, float $amount, int $organization_id)
    {
        return $this->gateway->checkout($user, $amount, $organization_id);
    }
}