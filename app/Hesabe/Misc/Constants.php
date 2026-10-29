<?php

namespace App\Hesabe\Misc;
/**
 * This class is used for defining constant.
 *
 * @author Hesabe
 */
class Constants
{
    public const VERSION = "2.0";

    //Payment API URL
    public const PAYMENT_API_URL = "https://api.hesabe.com";

    // Get below values from Merchant Panel, Profile section
    public const ACCESS_CODE = "56539eb4-f604-4eeb-9c53-56ca120d11b8";
    public const MERCHANT_SECRET_KEY = "jAvkVmK6XN3e8Lw4PDRoqL5ZBd0zbwWx";
    public const MERCHANT_IV = "XN3e8Lw4PDRoqL5Z";
    public const MERCHANT_CODE = "21430520";

    // This URL are defined by you to get the response from Payment Gateway after success and failure
    public const RESPONSE_URL = "http://api2-kit-php.test/index.php/?id=842217";
    public const FAILURE_URL = "http://api2-kit-php.test/index.php/?id=842217";

    //Codes
    public const SUCCESS_CODE = 200;
    public const AUTHENTICATION_FAILED_CODE = 501;
}
