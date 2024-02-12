<?php

namespace App\Enums;

enum ConfigValueEnum: int
{
    case DATETIME_ASTEXT_FORMAT = 3;
    case DATE_ASTEXT_FORMAT = 4;
    case REPORT_BIG_FONT_SIZE = 5;
    case REPORT_MEDIUM_FONT_SIZE = 6;
    case REPORT_SMALL_FONT_SIZE = 7;

    public static function get(ConfigValueEnum $case, int | string | array $default = ''): int | string | array
    {
        $value = '';
        switch ($case) {

            case self::DATETIME_ASTEXT_FORMAT:
                $value = config('app.datetime_astext_format');
                break;
            case self::DATE_ASTEXT_FORMAT:
                $value = config('app.date_astext_format');
                break;

            case self::REPORT_BIG_FONT_SIZE:
                $value = config('app.reports.big_font_size');
                break;
            case self::REPORT_MEDIUM_FONT_SIZE:
                $value = config('app.reports.medium_font_size');
                break;
            case self::REPORT_SMALL_FONT_SIZE:
                $value = config('app.reports.small_font_size');
                break;
        }
        return $value ?? $default;
    }

}
