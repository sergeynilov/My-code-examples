<?php

namespace App\Library\Services\Interfaces;

use App\Enums\AppReportFontSizeEnum;

interface AppReportsInterface
{
    public function setContentTableStyle(string $value);

    public function setContentTableTdStyle(string $value);

    public function setShowCreatedAt(bool $value);

    public function generateContent(): \Barryvdh\DomPDF\PDF;

    public function loadPdf($pdf): \Symfony\Component\HttpFoundation\Response;

    public function getTopCenteredBlockContent(bool $value, AppReportFontSizeEnum $fontSize): string;

    public function getLeftTopBlockContent(bool $value, AppReportFontSizeEnum $fontSize): string;

    public function getRightTopBlockContent(bool $value, AppReportFontSizeEnum $fontSize): string;

    public function getLeftBottomBlockContent(bool $value, AppReportFontSizeEnum  $fontSize): string;

    public function getRightBottomBlockContent(bool $value, AppReportFontSizeEnum  $fontSize): string;
}
