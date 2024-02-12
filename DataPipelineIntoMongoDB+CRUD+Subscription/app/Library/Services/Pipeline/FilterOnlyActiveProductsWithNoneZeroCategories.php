<?php
namespace App\Library\Services\Pipeline;

use Carbon\Carbon;
use App\Models\PipelineCategory;
use App\Models\Product;
use App\Models\PipelineProduct;
use App\Models\PipelineProductCategory;
use Illuminate\Database\Eloquent\Model;

class FilterOnlyActiveProductsWithNoneZeroCategories
{
    /**
     * Check PipelineCategory model(from other db connection) and return to next functionality if it is active and in_stock = true and stock_qty > 0
     * Check PipelineProduct model(from other db connection) and return to next functionality if it has active products
     *
     * @param PipelineProduct $pipelineProduct
     * @return PipelineProduct
     */
    public function handle(PipelineProduct $pipelineProduct, $next)
    {
        $pipelineProductCategoriesCount = PipelineProductCategory
            ::getByProductId($pipelineProduct->id)
//            ->with('pipelineProductCategories')
            ->with('pipelineCategory')
            ->whereHas('pipelineCategory', function ($pipelineCategory) {
                $pipelineCategory->where(with(new PipelineCategory)->getTable().'.active', true); // Need to get only active categories
            })
            ->count();
        if($pipelineProductCategoriesCount > 0 ) {
            $this->markPipelineProductAsExported($pipelineProduct);
            return $next($pipelineProduct);
        }
        return false; // need to skip products without categories
    }

    /**
     * Execute the database request to mark product as imported
     *
     * @param PipelineProduct $pipelineProduct
     * @return PipelineProduct
     */
    protected function markPipelineProductAsExported(PipelineProduct $pipelineProduct): bool
    {
        $success = $pipelineProduct->update([
            'imported_date' => Carbon::now(config('app.timezone')),
        ]);
        return $success;
    }
}

