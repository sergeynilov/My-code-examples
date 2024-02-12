<?php
namespace App\Library\Services\Pipeline;

use Carbon\Carbon;
use App\Models\Category;
use App\Models\Product;
use App\Models\PipelineProduct;
use App\Models\PipelineProductCategory;
use Illuminate\Database\Eloquent\Model;

class FilterOnlyProductCategoriesWithExistingProductCategory
{
    /**
     * Check Category model(current mongo db connection) if there are inserted in prior pipeline Category model with
     * category_id(from PipelineProductCategory) = Category.source_id

     * Check Product model(current mongo db connection) if there are inserted in prior pipeline Product model with
     * product_id(from PipelineProductProduct) = Product.source_id

     * @param PipelineProduct $pipelineProduct
     * @return PipelineProduct
     */
    public function handle(PipelineProductCategory $pipelineProductCategory, $next)
    {
        $categoriesCount = Category
            ::getBySourceId($pipelineProductCategory->category_id)
            ->count();

        $productsCount = Product
            ::getBySourceId($pipelineProductCategory->product_id)
            ->count();

        if($categoriesCount > 0 & $productsCount > 0) {
            return $next($pipelineProductCategory);
        }
        return false; // need to skip products without categories
    }
}

