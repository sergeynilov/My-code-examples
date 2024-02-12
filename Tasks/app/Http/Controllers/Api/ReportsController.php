<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Library\Reports\TaskDetailsReport;
use App\Library\Services\Interfaces\AppReportStyleLayoutInterface;
use App\Repositories\Interfaces\CrudRepositoryInterface;

class ReportsController extends Controller
{
    protected CrudRepositoryInterface $taskCrudRepository;
    protected AppReportStyleLayoutInterface $appReportStyleLayoutInterface;

    public function __construct(CrudRepositoryInterface $taskCrudRepository)
    {
        $this->taskCrudRepository = $taskCrudRepository;
        $this->appReportStyleLayoutInterface = \App::make(AppReportStyleLayoutInterface::class);
    }

    public function showTaskDetailsReport(int $taskId): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $task = $this->taskCrudRepository->get(id: $taskId);
            $taskDetailsReport = new TaskDetailsReport;
            $taskDetailsReport->setTask($task);

            $taskDetailsReport->setBodyFontName($this->appReportStyleLayoutInterface::getBodyFontName());
            $taskDetailsReport->setBodyFontSize($this->appReportStyleLayoutInterface::getBodyFontSize());
            $taskDetailsReport->setContentTableStyle($this->appReportStyleLayoutInterface::getContentTableStyle());
            $taskDetailsReport->setContentTableTdStyle($this->appReportStyleLayoutInterface::getContentTableTbStyle());
            $taskDetailsReport->setShowCreatedAt(true);

            $generatedContent = $taskDetailsReport->generateContent();
            return $taskDetailsReport->loadPdf($generatedContent);
        } catch (\Exception|\Error $e) {
            \Log::info($e->getMessage());
        }
    }
}
