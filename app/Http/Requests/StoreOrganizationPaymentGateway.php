<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationPaymentGateway extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = strtolower($this->route('organization-payment-gateways'));

        $rules = [];
 
        if ($type == 'MyFatorah') {
            $rules = [
                'api_key' => 'required',
                'secret_key' => 'required',
                'merchant_id' => 'required',
            ];
        } else if($type == 'Hesabe') {
            $rules = [   
                'merchant_id' => 'required',
                'access_key' => 'required',
                'secret_key' => 'required',
                'iv_key' => 'required',
            ];
        } else if($type == 'tap') {
            $rules = [
                'secret_key' => 'required',
                'publishale_key' => 'required',
            ];
        } 

        return $rules;
    }
}
