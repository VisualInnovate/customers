<?php

return [
    'default' => App\Services\Payment\Gateways\HesabeGateway::class,
    'hesabe' => [
        'class' => App\Services\Payment\Gateways\HesabeGateway::class,
        'merchant_code' => env('HESABE_MERCHANT_CODE'),
        'access_code' => env('HESABE_ACCESS_CODE'),
        'secret_key' => env('HESABE_SECRET_KEY'),
        'iv_key' => env('HESABE_IV_KEY'),
        'environment' => env('HESABE_TEST_ENV') == 'true' // test env ?
    ],
];
