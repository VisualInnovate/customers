<?php

namespace App\Helpers;
use Cache;

use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Setting;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;
use Symfony\Component\Mime\Part\HtmlPart;

class CustomHelper {
    public static function config($key){
        $config = Setting::where('key', $key)->first();

        if($config){
            return $config->value;
        } else {
            return NULL;
        }
    }
}
