<?php

namespace App\Library\Services;

use App\Library\Services\Interfaces\AppReportStyleLayoutInterface;

readonly class AppReportStyleLayout implements AppReportStyleLayoutInterface
{
    public static function getBodyFontName(): string {
        return 'DejaVu Sans';
    }
    public static function getBodyFontSize(): string {
        return '12';
    }

    public static function getContentTableStyle(): string
    {
        return 'border: 4px double black; width: 100%; padding: 4px; margin: 4px; ';
    }

    public static function getContentTableTbStyle(): string
    {
        return 'width: 100%; padding: 2px; margin: 2px; border: 1px solid grey;';
    }
}
