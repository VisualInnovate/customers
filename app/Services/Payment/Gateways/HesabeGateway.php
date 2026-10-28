<?php

namespace App\Services\Payment\Gateways;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Hesabe\Payment\HesabeCrypt;
use Hesabe\Payment\Payment;
use JetBrains\PhpStorm\NoReturn;

class HesabeGateway implements Gateway
{
    public function __construct(
        private readonly string $merchant_code,
        private readonly string $secret_key,
        private readonly string $iv_key,
        private readonly string $access_code,
        private readonly bool $environment,
    ) {}
    public function checkout($user, $amount, $organization_id): string
    {
        $encryptedData = HesabeCrypt::encrypt($this->buildPayload($user, $amount, $organization_id),
            $this->secret_key, $this->iv_key);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->getRedirectBaseUrl()."/checkout",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array('data' => $encryptedData),
            CURLOPT_HTTPHEADER => array(
                "accessCode: $this->access_code",
                "Accept: application/json"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $decryptedResponse = HesabeCrypt::decrypt($response, $this->secret_key, $this->iv_key);
        $jsonData = json_decode($decryptedResponse, true);

        if (isset($jsonData['status']) && !$jsonData['status']) {
            return false;
        }

        $token = $jsonData['response']['data'];
        $baseUrl = $this->getRedirectBaseUrl();

        return "$baseUrl/payment?data=$token";
    }

    public function buildPayload(User $user, $amount, $org_id): false|string
    {
        return json_encode([
            "merchantCode" => $this->merchant_code,
            "amount" => $amount,
            "paymentType" => "0", // indirect payment
            "responseUrl" => route('payments.fallback.hesabe'),
            "failureUrl" => route('payments.fallback.hesabe'),
            "orderReferenceNumber" => $org_id . '|' . uuid_create(),
            "name" => $user->full_name,
            "mobile_number" => $user->phone,
            "email" => $user->email,
            "variable1" => $org_id,
            "version" => "2.0",
        ]);
    }

    public function decrypt($data)
    {
        return json_decode(HesabeCrypt::decrypt($data, $this->secret_key, $this->iv_key));
    }

    public function checkPayment($paymentId)
    {
        // TODO: Implement checkPayment() method.
    }

    private function getRedirectBaseUrl(): string
    {
        $endpoints = [
            true => 'https://sandbox.hesabe.com',
            false => 'https://api.hesabe.com'
        ];

        return $endpoints[$this->environment];
    }
}