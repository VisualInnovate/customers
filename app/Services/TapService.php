<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\PaymentMethod;
use App\Models\PaymentGateway;
use App\Models\SavedCard;
use Illuminate\Support\Facades\Log;

class TapService
{
    protected $secretKey;
    protected $baseUrl;
    protected $paymentKeys;

    public function __construct()
    {
        $tapInfo = PaymentGateway::where('name', 'Tap')->first();
        
        if (!$tapInfo || empty($tapInfo->metadata)) {
            throw new \Exception('Payment gateway metadata is missing or invalid.');
        }
        
        $this->paymentKeys = json_decode($tapInfo->metadata, true);
        $this->secretKey = $this->paymentKeys['secret_key'];
        $this->baseUrl = 'https://api.tap.company/v2';
    }

    public function handlePayment($amount, $planId = NULL)
    {
        // Check if user has saved card
        $paymentMethod = SavedCard::where('organization_id', session()->get('current_organization'))
            ->first();

        if ($paymentMethod) {
            // User has saved card, process as recurring payment
            return $this->processRecurringCharge(
                $amount,
                $paymentMethod->customer_id,
                $paymentMethod->payment_agreement_id,
                $paymentMethod->card_id
            );
        }

        // First time payment - will save card
        $user = auth()->user();
        return $this->processFirstCharge($amount, [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => [
                'country_code' => '965',
                'number' => $user->phone
            ]
        ]);
    }

    protected function processFirstCharge($amount, $customer)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/charges", [
                'amount' => $amount,
                'currency' => 'KWD',
                'save_card' => true,
                'customer_initiated' => true,
                'threeDSecure' => true,
                'description' => 'Initial payment with card saving',
                'metadata' => [
                    'udf1' => 'Initial Save Card Payment'
                ],
                'reference' => [
                    'transaction' => 'txn_' . uniqid(),
                    'order' => 'ord_' . uniqid()
                ],
                'receipt' => [
                    'email' => true,
                    'sms' => true
                ],
                'customer' => $customer,
                'source' => [
                    'id' => 'src_all'
                ],
                'post' => [
                    'url' => route('tap.webhook')
                ],
                'redirect' => [
                    'url' => route('tap.callback')
                ]
            ]);

            $result = $response->json();
            Log::info(['Tap payment response:' => $result]);
            
            if (isset($result['transaction']['url'])) {
                return [
                    'status' => true,
                    'is_new' => true,
                    'payment_url' => $result['transaction']['url']
                ];


            }

            return [
                'status' => false,
                'message' => 'Failed to create payment URL'
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
            Log::info(['Tap payment failed response:' => $result]);
        }
    }

    protected function processRecurringCharge($amount, $customerId, $agreementId, $cardId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/charges", [
                'amount' => $amount,
                'currency' => 'KWD',
                'customer_initiated' => false,
                'save_card' => false,
                'payment_agreement' => [
                    'id' => $agreementId
                ],
                'description' => 'Recurring payment',
                'metadata' => [
                    'udf1' => 'Recurring Charge'
                ],
                'reference' => [
                    'transaction' => 'txn_' . uniqid(),
                    'order' => 'ord_' . uniqid()
                ],
                'customer' => [
                    'id' => $customerId
                ],
                'source' => [
                    'id' => $cardId
                ],
                'post' => [
                    'url' => route('tap.webhook')
                ]
            ]);

            $result = $response->json();

            return [
                'status' => true,
                'is_new' => false,
                'charge_id' => $result['id'] ?? null,
                'message' => 'Recurring payment processed'
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}