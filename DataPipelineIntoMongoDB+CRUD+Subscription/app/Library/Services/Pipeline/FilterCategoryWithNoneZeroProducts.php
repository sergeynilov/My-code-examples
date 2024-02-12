<?php
namespace App\Library\Services\Pipeline;

use Carbon\Carbon;
use App\Models\{PipelineCategory, PipelineProduct, PipelineProductCategory};

class FilterCategoryWithNoneZeroProducts
{
    /**
     * Check PipelineProduct model(from other db connection) and return to next functionality if it has active products
     *
     * @param PipelineCategory $pipelineCategory
     * @return PipelineCategory
     */
    public function handle(PipelineCategory $pipelineCategory, $next)
    {
        $pipelineProductCategoriesCount = PipelineProductCategory
            ::getByCategoryId($pipelineCategory->id)
            ->with('pipelineProduct')
            ->whereHas('pipelineProduct', function ($pipelineProduct) {
                $pipelineProduct->where(with(new PipelineProduct)->getTable().'.status', 'A'); // Need to get only active pipelineProducts
            })
            ->count();
        if($pipelineProductCategoriesCount > 0 ) {
            $this->markPipelineCategoryAsExported($pipelineCategory);
            return $next($pipelineCategory);
        }
        return false;
    }

    /**
     * Execute the database request to mark category as imported
     *
     * @param PipelineCategory $pipelineCategory
     * @return PipelineCategory
     */
    protected function markPipelineCategoryAsExported(PipelineCategory $pipelineCategory): bool
    {
        $success = $pipelineCategory->update([
            'imported_date' => Carbon::now(config('app.timezone')),
        ]);
        return $success;
    }
}
