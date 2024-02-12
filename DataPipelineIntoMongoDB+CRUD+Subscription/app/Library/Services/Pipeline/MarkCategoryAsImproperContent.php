<?php

namespace App\Library\Services\Pipeline;

use App\Enums\CheckValueType;
use App\Models\Category;
use App\Models\Settings;
use App\Models\PipelineCategory;
use App\Models\BadWord;
use Snipe\BanBuilder\CensorWords;
use Illuminate\Support\Arr;

class MarkCategoryAsImproperContent
{
    private $textFields = ['name', 'HREN', 'description', 'created'];

    /**
     * Check PipelineCategory model as starred in the database
     *
     * @param PipelineCategory $pipelineCategory
     *
     * @return PipelineCategory
     */
    public function handle(PipelineCategory $pipelineCategory, $next)
    {
        $improper_content_set_active = Settings::getValue('improper_content_set_active', CheckValueType::cvtString,'N');
        $censor                      = new CensorWords;

        $badWords= BadWord::get()->pluck('word')->toArray();
        $censor->badwords = Arr::collapse([$censor->badwords, array_values($badWords)]);

        $pipelineCategoryAttributes = $pipelineCategory->getAttributes();
        $importNotices = '';
        foreach ($this->textFields as $textField) {
            if (Arr::exists($pipelineCategoryAttributes, $textField)) {
                $censoredCategoryFieldValue = $censor->censorString($pipelineCategoryAttributes[$textField], true);
                if ($censoredCategoryFieldValue['clean'] != $pipelineCategoryAttributes[$textField]) {
                    $pipelineCategory->{$textField}   = $censoredCategoryFieldValue['clean'];
                    $pipelineCategory->active = $improper_content_set_active == 'Y' ? Category::ACTIVE_YES :
                        Category::ACTIVE_NO;
                    $importNotices            .= 'has Improper Content in "' . $textField . '" field : word "' .
                                                 Arr::join($censoredCategoryFieldValue['matched'], ', ') .
                                                 '" =>' . $censoredCategoryFieldValue['orig'] . ';';
                }
            }
        }
        $pipelineCategory->import_notices = $importNotices;

        return $next($pipelineCategory);
    }

}
