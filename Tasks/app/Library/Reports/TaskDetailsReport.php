<?php

namespace App\Library\Reports;

use App\Enums\AppReportFontSizeEnum;
use App\Enums\ConfigValueEnum;
use App\Library\Facades\DateConv;
use App\Models\Task;
use Carbon\Carbon;
use App\Library\Services\Interfaces\AppReportsInterface;

class TaskDetailsReport implements AppReportsInterface
{

    /** @var Task $task - task which must be shown in the report */
    protected Task $task;
    protected string $bodyFontName = '';
    protected string $bodyFontSize = '10px';
    protected string $contentTableStyle = ' border:2px double black; width: 100%; ';
    protected string $contentTableTdStyle = ' border:1px dotted green; padding : 20px';
    protected bool $showCreatedAt = false;

    public function setBodyFontName(string $value)
    {
        $this->bodyFontName = $value;
    }

    public function setBodyFontSize(string $value)
    {
        $this->bodyFontSize = $value;
    }

    public function setContentTableStyle(string $value)
    {
        $this->contentTableStyle = $value;
    }

    public function setContentTableTdStyle(string $value)
    {
        $this->contentTableTdStyle = $value;
    }

    public function setShowCreatedAt(bool $value)
    {
        $this->showCreatedAt = $value;
    }

    /**
     * @param Illuminate\Database\Eloquent\Model $value
     */
    public function setTask(Task $value)
    {
        $this->task = $value;
    }

    public function generateContent(): \Barryvdh\DomPDF\PDF
    {
        $data = [
            'task' => $this->task,
            'bodyFontName' => $this->bodyFontName,
            'bodyFontSize' => $this->bodyFontSize,
            'contentTableStyle' => $this->contentTableStyle,
            'contentTableTdStyle' => $this->contentTableTdStyle,
            'showCreatedAt' => $this->showCreatedAt,
        ];

        $data['topCenteredBlockContent'] = $this->getTopCenteredBlockContent(true, AppReportFontSizeEnum::BIG);
        $data['leftTopBlockContent'] = $this->getLeftTopBlockContent(true, AppReportFontSizeEnum::MEDIUM);
        $data['rightTopBlockContent'] = $this->getRightTopBlockContent(true, AppReportFontSizeEnum::SMALL);
        $data['leftBottomBlockContent'] = $this->getLeftBottomBlockContent(true, AppReportFontSizeEnum::MEDIUM);
        $data['rightBottomBlockContent'] = $this->getRightBottomBlockContent(true, AppReportFontSizeEnum::SMALL);
        return \PDF::loadView('reports/task-details-report', $data);
    }
    // public function generateContent(): \Barryvdh\DomPDF\PDF

    public function loadPdf($pdf): \Symfony\Component\HttpFoundation\Response
    {
        $filename = 'task-details-report-' . \Str::slug($this->task->title) . '.pdf';
        return $pdf->download($filename);
    }

    public function getTopCenteredBlockContent(bool $value, AppReportFontSizeEnum  $fontSize): string
    {
        return $this->wrapTextWithFontSizeStyle('Task Details Report of "' . $this->task->title, $fontSize);
    }

    public function getLeftTopBlockContent(bool $value, AppReportFontSizeEnum  $fontSize): string
    {
        return $this->wrapTextWithFontSizeStyle(DateConv::getFormattedDateTime(Carbon::now(config('app.timezone'))), $fontSize);
    }

    public function getRightTopBlockContent(bool $value, AppReportFontSizeEnum  $fontSize): string
    {
        return $this->wrapTextWithFontSizeStyle(config('app.report_made_location'), $fontSize);
    }

    public function getLeftBottomBlockContent(bool $value, AppReportFontSizeEnum  $fontSize): string
    {
        return $this->wrapTextWithFontSizeStyle(config('app.copyright_text'), $fontSize);
    }

    public function getRightBottomBlockContent(bool $value, AppReportFontSizeEnum  $fontSize): string
    {
        return $this->wrapTextWithFontSizeStyle(config('app.contact_us_text'), $fontSize);
    }

    public function wrapTextWithFontSizeStyle(string $text, AppReportFontSizeEnum  $fontSize): string
    {
        if($fontSize === AppReportFontSizeEnum::BIG) {
            return ' <span style="' . ConfigValueEnum::get(ConfigValueEnum::REPORT_BIG_FONT_SIZE) . '" >' . $text . ' </span>';
        }
        if($fontSize === AppReportFontSizeEnum::MEDIUM) {
            return ' <span style="' . ConfigValueEnum::get(ConfigValueEnum::REPORT_MEDIUM_FONT_SIZE) . '" >' . $text . ' </span>';
        }
        if($fontSize === AppReportFontSizeEnum::SMALL) {
            return ' <span style="' . ConfigValueEnum::get(ConfigValueEnum::REPORT_SMALL_FONT_SIZE) . '" >' . $text . ' </span>';
        }
    }
}
