<?php

namespace App\Resolvers;

use DB;

class PaymentPlatformResolver
{
    public function resolveService($paymentPlatform)
    {
        $name = strtolower($paymentPlatform);
        $service = config("services.{$name}.class");
        
        if ($service) {
            return resolve($service);
        }

        throw new \Exception(__('The selected platform is not in the configuration file'));
    }
}