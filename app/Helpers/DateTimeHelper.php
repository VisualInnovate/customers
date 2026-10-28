<?php 

namespace App\Helpers;

use App\Models\Organization;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Nightwatch\Facades\Nightwatch;


class DateTimeHelper
{
    public static function formatDate(string $dateTimeString)
    {
        $dt = Carbon::create($dateTimeString);
        $dateFormat = Setting::where('key', '=', 'date_format')->first()->value;
        $timeFormat = Setting::where('key', '=', 'time_format')->first()->value;

        return $dt->format($dateFormat . ' ' . $timeFormat); 
    }

    public static function getOrganizationTimezone(Organization $organization = null)
    {
        Nightwatch::dontSample();
        $timezone = config('app.timezone'); // Default to UTC

        // Use singleton organization if not provided
        $organization = $organization ?? current_organization();

        if ($organization) {
            $metadata = $organization->metadata;
            $metadata = isset($metadata) ? json_decode($metadata, true) : null;

            if ($metadata && isset($metadata['timezone'])) {
                $timezone = $metadata['timezone'];
            }
        }
        return $timezone;
    }

    public static function convertToOrganizationTimezone($date)
    {
        return Carbon::parse($date)->setTimezone(self::getOrganizationTimezone());
    }

    public static function convertToCompanyTimezone($date)
    {
        $timezone = setting('timezone', config('app.timezone'));

        return Carbon::parse($date)->setTimezone($timezone);
    }

    public static function formatDateWithoutHours($date)
    {
        return $date->format('d M Y'); // Format without hours, minutes, and seconds
    }
}

