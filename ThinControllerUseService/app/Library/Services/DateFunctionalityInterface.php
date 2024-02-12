<?php

namespace App\Library\Services;

interface DateFunctionalityInterface
{
    public static function getFormattedTime($time = ''):string;

    public static function getFormattedDateTime($datetime, $datetimeFormat = 'mysql', $outputFormat = ''): string;

    public static function isValidTimeStamp($timestamp);

    public static function getFormattedDate($date, $dateFormat = 'mysql', $outputFormat = ''): string;


    public static function getDateFormat($format = '') : string;


    public static function getDateTimeFormat($format = '') : string;

}
