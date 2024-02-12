<?php

namespace App\Library\Services\Pipeline;

use App\Enums\CheckValueType;
use Carbon\Carbon;
use App\Models\Settings;
use App\Models\Product;
use App\Models\PipelineProduct;
use App\Models\BadWord;
use Snipe\BanBuilder\CensorWords;
use Illuminate\Support\Arr;

class MarkProductAsImproperContent
{
    private $textFields = ['title', 'HREN', 'description', 'short_description', 'created'];

    /**
     * Check PipelineProduct model as starred in the database
     *
     * @param PipelineProduct $pipelineProduct
     *
     * @return PipelineProduct
     */
    public function handle(PipelineProduct $pipelineProduct, $next)
    {
        $improper_content_set_active = Settings::getValue('improper_content_set_active', CheckValueType::cvtString,'N');
        $censor                      = new CensorWords;

        $badWords= BadWord::get()->pluck('word')->toArray();
        $censor->badwords = Arr::collapse([$censor->badwords, array_values($badWords)]);

        $pipelineProductAttributes = $pipelineProduct->getAttributes();
        $importNotices = '';
        foreach ($this->textFields as $textField) {
            if (Arr::exists($pipelineProductAttributes, $textField)) {
                $censoredProductFieldValue = $censor->censorString($pipelineProductAttributes[$textField], true);
                if ($censoredProductFieldValue['clean'] != $pipelineProductAttributes[$textField]) {
                    $pipelineProduct->{$textField}   = $censoredProductFieldValue['clean'];
                    $pipelineProduct->status = $improper_content_set_active == 'Y' ? Product::STATUS_ACTIVE :
                        Product::STATUS_INACTIVE;
                    $importNotices            .= 'has Improper Content in "' . $textField . '" field : word "' .
                                                 Arr::join($censoredProductFieldValue['matched'], ', ') .
                                                 '" =>' . $censoredProductFieldValue['orig'] . ';';
                }
            }
        }
        $pipelineProduct->import_notices = $importNotices;

        return $next($pipelineProduct);
    }

}
