<?php

namespace App\Services;

use Helper;
use DateTime;
use Carbon\Carbon;
use CurrencyHelper;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Hesabe\Misc\Constants;
use App\Models\BillingPayment;
use App\Models\PaymentGateway;
use App\Events\NewPaymentEvent;
use App\Models\BillingTransaction;
use Illuminate\Support\Facades\DB;
use App\Hesabe\Misc\PaymentHandler;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as HttpClient;
use App\Hesabe\Libraries\HesabeCrypt;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Config;
use App\Traits\ConsumesExternalServices;
use App\Hesabe\Helpers\ModelBindingHelper;
use GuzzleHttp\Exception\RequestException;
use App\Hesabe\Models\HesabeCheckoutResponseModel;

class HesabeService
{
    public $paymentApiUrl;
    public $secretKey;
    public $ivKey;
    public $accessCode;
    public $merchantCode;
    public $hesabeCheckoutResponseModel;
    public $modelBindingHelper;
    public $hesabeCrypt;
    public $paymentKeys;

    public function __construct()
    {
        $paypalInfo = PaymentGateway::where('name', 'Hesabe')->first();
    
        if (!$paypalInfo || empty($paypalInfo->metadata)) {
            throw new \Exception('Payment gateway metadata is missing or invalid.');
        }
    
        $this->paymentKeys = json_decode($paypalInfo->metadata, true); // Decode as associative array
    
        $this->secretKey                    = $this->paymentKeys['secret_key'];
        $this->ivKey                        = $this->paymentKeys['iv_key'];
        $this->accessCode                   = $this->paymentKeys['access_code'];
        $this->merchantCode                 = $this->paymentKeys['merchant_code'];
    
        $this->paymentApiUrl                = Constants::PAYMENT_API_URL;
        $this->hesabeCheckoutResponseModel  = new HesabeCheckoutResponseModel();
        $this->modelBindingHelper           = new ModelBindingHelper();
        $this->hesabeCrypt                  = new HesabeCrypt();
    }
    

    public function handlePayment($amount, $plan)
    {
        // Initialize the Payment request encryption/decryption library using the Secret Key and IV Key from the configuration
        $paymentHandler = new PaymentHandler($this->paymentApiUrl, $this->merchantCode, $this->secretKey, $this->ivKey, $this->accessCode);
        $request = [];
        $date = new DateTime();
        $request['currency'] = 'KWD';
        $request['paymentType'] = 0;
        $request['orderReferenceNumber'] = $date->getTimestamp();
        $request['version'] = 2.0;
        $request['variable1'] = $plan;
        $request['variable2'] = "";
        $request['variable3'] = ""; 
        $request['variable4'] = "";
        $request['variable5'] = "";
        $request['merchantCode'] = "21430520";
        $request['responseUrl'] = env('RESPONSEURL');
        $request['failureUrl'] = env('FAILUREURL');

        try {
            // Getting the payment data into request object
            $requestData = $this->modelBindingHelper->getCheckoutRequestData($amount, $request);
            $requestData->merchantCode = $this->merchantCode;

            // POST the requested object to the checkout API and receive back the response
            $response = $paymentHandler->checkoutRequest($requestData);
            //Get encrypted and decrypted checkout data response
            [$encryptedResponse, $hesabeCheckoutResponseModel] = $this->getCheckoutResponse($response);
            // check the response and validate it
            if ($hesabeCheckoutResponseModel->status == false && $hesabeCheckoutResponseModel->code != Constants::SUCCESS_CODE) {
                /* echo "<p style='word-break: break-all;'> <b>Encrypted Data</b>:- " . $encryptedResponse . "</p>";
                echo "<p><b>Decrypted Data</b>:- </p>";
                echo "<pre>";
                print_r($hesabeCheckoutResponseModel);
                exit;
                */
                
                return response()->json($hesabeCheckoutResponseModel);
            }
            // Redirect the user to the payment page using the token from the checkout API response
            return $this->redirectToPayment($hesabeCheckoutResponseModel->response['data']);
        } catch (\Exception $exception) {
            $data = [
                "message" => __("Seller credentials has an issue, Please make sure of seller payment credentials"),
                "status" => false
            ];

            return $data;
        }
    }

    public function redirectToPayment($token)
    {
        return "$this->paymentApiUrl/payment?data=$token";
        // header("Location: $this->paymentApiUrl/payment?data=$token");
        // return redirect()->away("$this->paymentApiUrl/payment?data=$token");
    }


    public function getPaymentResponse($responseData)
    {
        //Decrypt the response received in the data query string
        $decryptResponse = $this->hesabeCrypt::decrypt($responseData, $this->secretKey, $this->ivKey);

        //De-serialize the decrypted response
        $decryptResponseData = json_decode($decryptResponse, true);

        //Binding the decrypted response data to the entity model
        $decryptedResponse = $this->modelBindingHelper->getPaymentResponseData($decryptResponseData);

        //return decrypted data
        return $decryptedResponse;
    }

    /**
     * Process the response after the form data has been requested.
     *
     * @return array De-serialize the decrypted response
     *
     */
    public function getCheckoutResponse($response)
    {
        // Decrypt the response from the checkout API
        $decryptResponse = $this->hesabeCrypt::decrypt($response, $this->secretKey, $this->ivKey);

        if (!$decryptResponse) {
            $decryptResponse = $response;
        }

        // De-serialize the JSON string into an object
        $decryptResponseData = json_decode($decryptResponse, true);
        //Binding the decrypted response data to the entity model
        $decryptedResponse = $this->modelBindingHelper->getCheckoutResponseData($decryptResponseData);

        //return encrypted and decrypted data
        return [$response, $decryptedResponse];
    }
}