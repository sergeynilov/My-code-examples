<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum AppReportFontSizeEnum: int
{
    case SMALL = 1;
    case MEDIUM = 2;
    case BIG = 3;

    public static function getCasesArray(): array
    {
        return self::cases();
    }

    public static function getLabelByKey(AppReportFontSizeEnum $key, bool $capitalize= false): string
    {
        foreach( self::cases() as $case ) {
            if($case->value === $key->value) {
                return $capitalize ? Str::ucfirst(Str::lower($case->name)) : $case->name;
            }
        }
        return '';
    }
}
