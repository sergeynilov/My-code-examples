<?php

namespace App\Library\Services;

interface DateFunctionalityServiceInterface
{
    public function getFormattedTime($time = ''):string;

    public function getFormattedDateTime($datetime, $datetime_format = 'mysql', $output_format = ''): string;

    public function isValidTimeStamp($timestamp);


    public function getFormattedDate($date, $date_format = 'mysql', $output_format = ''): string;


    public function getDateFormat($format = '') : string;


    public function getDateTimeFormat($format = '') : string;

}
