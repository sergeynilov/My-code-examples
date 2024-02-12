<?php

namespace App\Library\Services;

use App\Library\Services\Interfaces\AppReportStyleLayoutInterface;

readonly class AlternativeReportStyleLayout implements AppReportStyleLayoutInterface
{
    public static function getBodyFontName():string {
        return 'DejaVu Sans';
    }
    public static function getBodyFontSize(): string {
        return '16';
    }
    public static function getContentTableStyle(): string
    {
        return 'width: 100%; padding: 4px; margin: 4px;   border: 2px solid black; border-radius: 10px;';
    }

    public static function getContentTableTbStyle(): string
    {
        return 'width: 100%; padding: 4px; margin: 4px;      border: 1px dotted gray;  border-bottom-left-radius: 10px;';
    }
}
