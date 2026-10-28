<?php

namespace App\Services\Payment\Gateways;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

interface Gateway
{
    public function checkout(User $user, float $amount, int $organization_id);

    public function checkPayment($paymentId);
}