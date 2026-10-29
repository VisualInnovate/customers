<?php

namespace App\Http\Controllers\ShopFiy;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use libphonenumber\PhoneNumberUtil;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use libphonenumber\PhoneNumberFormat;

class shopfiyController extends Controller
{
    private $accessToken;
    private $phoneNumberId;
    private $apiVersion;
 
    public function index(Request $request)
    {
        $data=$request->all();
        $billingAddress = $request->input('shipping_address', []);
 
          if (!empty($billingAddress['phone'])) {
         
            $phone = $billingAddress['phone'] ?? null;
            $countryCode = $billingAddress['country_code'] ?? 'US';
        
            $formattedPhone = $this->formatPhoneNumber($phone, $countryCode);
        
            $request = new Request([
                'first_name' => $billingAddress['first_name'] ?? null,
                'last_name' => $billingAddress['name'] ?? "test",
                'phone' => $formattedPhone,
                'email' => $data['email'] ?? null,
                'address' => $billingAddress['address1'] ?? null,
                'created_by' => 0,
            ]);
        
            return $this->store($request);
        }
           }

    public function store(Request $request)
    {
        $existingContact = Contact::where('phone', $request->phone)->first();

        if ($existingContact) {
             return response()->json([
                'message' => 'already found',
                'contact' => $existingContact
            ], 409);  

           
        }
    
        $contact = Contact::create([
            'organization_id' => 15,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'contact_group_id' => 3,
             'created_by'=>$request->created_by,
             'created_at'=>now(),
             'updated_at'=>now(),
        ]);

        return response()->json([
            'message' => 'Contact created successfully',
            'contact' => $contact
        ]);
    }

    function formatPhoneNumber($phone, $countryCode) {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $numberProto = $phoneUtil->parse($phone, $countryCode);
            return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);  
        } catch (\Exception $e) {
            return null;  
        }
    }

  
}