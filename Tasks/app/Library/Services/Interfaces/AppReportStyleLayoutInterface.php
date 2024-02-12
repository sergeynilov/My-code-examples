<?php

namespace App\Library\Services\Interfaces;

interface AppReportStyleLayoutInterface
{
    public static function getBodyFontName(): string;

    public static function getBodyFontSize(): string;

    public static function getContentTableStyle(): string;

    public static function getContentTableTbStyle(): string;
}
