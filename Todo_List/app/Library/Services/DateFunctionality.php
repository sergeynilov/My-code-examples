<?php

namespace App\Library\Services;

use Carbon\Carbon;
// Library/Services/DateFunctionalityServiceInterface.php
use App\Library\Services\DateFunctionalityServiceInterface;

class DateFunctionality implements DateFunctionalityServiceInterface
{
//    public const WATERMARK_PATH = '/img/hostels_brand_small.jpg'; //         $watermark_path = public_path    function

    private static $time_format = 'H:i';
    private static $date_numbers_format = 'Y-m-d';
    private static $datetime_numbers_format = 'Y-m-d H:i';

    private static $date_astext_format = 'j F, Y';
    private static $datetime_astext_format = 'j F, Y g:i A';

    public function getFormattedTime($time = ''):string
    {
        if (empty($time)) {
            return '';
        }
        $value = Carbon::parse($time);

        return $value->format(self::$time_format);
    }


    public function getFormattedDateTime($datetime, $datetime_format = 'mysql', $output_format = ''): string
    {
        if (empty($datetime)) {
            return '';
        }
        if (empty($datetime_format)) {
            $datetime_format = 'mysql';
        }
        if ($datetime_format == 'mysql' and ! self::isValidTimeStamp($datetime)) {

            if ($output_format == \App\Enums\DatetimeOutputFormat::dofAgoFormat) {
                return Carbon::createFromTimestamp(strtotime($datetime))->diffForHumans();
            }
            $datetime_format = self::getDateTimeFormat(\App\Enums\DatetimeOutputFormat::dofAsText);
            $ret             = Carbon::createFromTimestamp(strtotime($datetime))->format($datetime_format);
            return $ret;
        }

        if (self::isValidTimeStamp($datetime)) {
            $datetime_format = self::getDateTimeFormat(\App\Enums\DatetimeOutputFormat::dofAsText);
            $ret             = Carbon::createFromTimestamp($datetime)->format($datetime_format);
            return $ret;
        }

        return (string)$datetime;
    }

    public function isValidTimeStamp($timestamp)
    {
        if (empty($timestamp)) {
            return false;
        }
//        \Log::info( '-1 isValidTimeStamp gettype($timestamp)::' . print_r( gettype($timestamp), true  ) );
//        \Log::info( '-1 isValidTimeStamp $timestamp::' . print_r( $timestamp, true  ) );

        if (gettype($timestamp) == 'object') {
            $timestamp = $timestamp->toDateTimeString();
        }

        return ((string)(int)$timestamp === (string)$timestamp)
               && ($timestamp <= PHP_INT_MAX)
               && ($timestamp >= ~PHP_INT_MAX);
    }


    public function getFormattedDate($date, $date_format = 'mysql', $output_format = ''): string
    {
        if (empty($date)) {
            return '';
        }
        $date_carbon_format = config('app.date_carbon_format');
        if ($date_format == 'mysql' /*and ! isValidTimeStamp($date)*/) {
            $date_format = self::getDateFormat(\App\Enums\DatetimeOutputFormat::dofAsText);
            $date        = Carbon::createFromTimestamp(strtotime($date))->format($date_format);

            return $date;
        }


        if (self::isValidTimeStamp($date)) {
            if (strtolower($output_format) == \App\Enums\DatetimeOutputFormat::dofAsText) {
                $date_carbon_format_as_text = config('app.date_carbon_format_as_text', '%d %B, %Y');

                return Carbon::createFromTimestamp($date,
                    Config::get('app.timezone'))->formatLocalized($date_carbon_format_as_text);
            }
            if (strtolower($output_format) == 'pickdate') {
                $date_carbon_format_as_pickdate = config('app.pickdate_format_submit');

                return Carbon::createFromTimestamp($date,
                    Config::get('app.timezone'))->format($date_carbon_format_as_pickdate);
            }

            return Carbon::createFromTimestamp($date,
                Config::get('app.timezone'))->format($date_carbon_format);
        }
        $A = preg_split("/ /", $date);
        if (count($A) == 2) {
            $date = $A[0];
        }
        $a = Carbon::createFromFormat($date_carbon_format, $date);
        $b = $a->format(self::getDateFormat(\App\Enums\DatetimeOutputFormat::dofAsText));

        return $a->format(self::getDateFormat(\App\Enums\DatetimeOutputFormat::dofAsText));
    }


    public function getDateFormat($format = ''): string
    {
        if (strtolower($format) == \App\Enums\DatetimeOutputFormat::dofAsNumbers) {
            return self::$date_numbers_format;
        }
        if (strtolower($format) == \App\Enums\DatetimeOutputFormat::dofAsText) {
            return self::$date_astext_format;
        }

        return self::$date_numbers_format;
    }


    public function getDateTimeFormat($format = ''): string
    {
        if (strtolower($format) == \App\Enums\DatetimeOutputFormat::dofAsNumbers) {
            return self::$datetime_numbers_format;
        }
        if (strtolower($format) == \App\Enums\DatetimeOutputFormat::dofAsText) {
            return self::$datetime_astext_format;
        }

        return self::$datetime_numbers_format;
    }

}


