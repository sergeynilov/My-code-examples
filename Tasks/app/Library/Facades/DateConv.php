<?php

namespace App\Library\Facades;

use App\Enums\ConfigValueEnum;
use Carbon\Carbon;

class DateConv
{
    public static function getFormattedDateTime($datetime): string
    {
        if (empty($datetime)) {
            return '';
        }
        return (Carbon::parse($datetime))->format(ConfigValueEnum::get(ConfigValueEnum::DATETIME_ASTEXT_FORMAT));
    }
}
