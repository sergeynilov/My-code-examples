<?php

namespace App\Library\Services;

use Carbon\Carbon;
use App\Library\Services\Pipeline\{FilterCategoryWithNoneZeroProducts, MarkCategoryAsImproperContent,
    FilterOnlyActiveProductsWithNoneZeroCategories, FilterOnlyProductCategoriesWithExistingProductCategory,
    MarkProductAsImproperContent};

use App\Models\{Category, Product, ProductCategory, PipelineCategory, PipelineProduct, PipelineProductCategory};

use Illuminate\Pipeline\Pipeline;

class TsProductsPipelineImport
{
    private $chunkItemsPerPage = 50;

    /**
     * Run import of Categories into Category model from PipelineCategory(other db connection) model
     *
     * @param PipelineCategory $pipelineCategory
     *
     * @return array
     */
    public function importPipelineCategories(bool $clearTable = false): array
    {
        if ($clearTable) {
            \DB::table((new Category)->getCollection())->delete();
        }

        $categoriesWithImportNotices  = [];
        $pipedCategoriesCount         = 0;
        $importCategoriesNoticesCount = 0;
        $sourceCategories             = PipelineCategory
            ::onlyActive()
            ->chunk($this->chunkItemsPerPage, function ($sourceCategories) use (
                &$pipedCategoriesCount,
                &$importCategoriesNoticesCount,
                &$categoriesWithImportNotices
            ) {
                foreach ($sourceCategories as $index => $sourcePipelineCategory) {
                    app(Pipeline::class)
                        ->send($sourcePipelineCategory)
                        ->through([
                            // Check if PipelineCategory has active products
                            FilterCategoryWithNoneZeroProducts::class,

                            //Check PipelineCategory model as starred in the database
                            MarkCategoryAsImproperContent::class,
                        ])->then(function ($sourcePipelineCategory) use (
                            &$pipedCategoriesCount,
                            &$importCategoriesNoticesCount,
                            &
                            $categoriesWithImportNotices
                        ) {
                            $newCategory = Category::create([
                                'name'           => $sourcePipelineCategory->name,
                                'active'         => $sourcePipelineCategory->active,
                                'description'    => $sourcePipelineCategory->description,
                                'source_id'      => $sourcePipelineCategory->id,
                                'updated_at'     => $sourcePipelineCategory->updated_at,
                                'created_at'     => Carbon::now(config('app.timezone')),
                                'import_notices' => $sourcePipelineCategory->import_notices ?? null,
                            ]);
                            $pipedCategoriesCount++;
                            if ( ! empty($sourcePipelineCategory->import_notices)) {
                                $categoriesWithImportNotices[] = $newCategory->_id;
                                $importCategoriesNoticesCount++;
                            }
                        });
                }
            });

        return [
            'pipedCategoriesCount'         => $pipedCategoriesCount,
            'importCategoriesNoticesCount' => $importCategoriesNoticesCount,
            'categoriesWithImportNotices'  => $categoriesWithImportNotices,
        ];

    }


    /**
     * Run import of Products into Product model from PipelineProduct(other db connection) model
     *
     * @param PipelineProduct $pipelineProduct
     *
     * @return array
     */
    public function importPipelineProducts(bool $clearTable = false): array
    {
        if ($clearTable) {
            \DB::table((new Product)->getCollection())->delete();
        }

        $productsWithImportNotices  = [];
        $pipedProductsCount         = 0;
        $importProductsNoticesCount = 0;
        $sourceProducts             = PipelineProduct
            ::getByStatus(PipelineProduct::STATUS_ACTIVE)
            ->chunk($this->chunkItemsPerPage, function ($sourceProducts) use (
                &$pipedProductsCount,
                &$importProductsNoticesCount,
                &$productsWithImportNotices
            ) {
                foreach ($sourceProducts as $index => $sourcePipelineProduct) {
                    app(Pipeline::class)
                        ->send($sourcePipelineProduct)
                        ->through([
                            // Check if it is active and in_stock = true and stock_qty > 0
                            FilterOnlyActiveProductsWithNoneZeroCategories::class,

                            //Check PipelineProduct model as starred in the database
                            MarkProductAsImproperContent::class,
                        ])->then(function ($sourcePipelineProduct) use (
                            &$pipedProductsCount,
                            &$importProductsNoticesCount,
                            &$productsWithImportNotices
                        ) {
                            $newProduct = Product::create([
                                'title'              => $sourcePipelineProduct->title,
                                'status'             => $sourcePipelineProduct->status,
                                'slug'               => $sourcePipelineProduct->slug,
                                'regular_price'      => $sourcePipelineProduct->regular_price,
                                'discount_price'     => $sourcePipelineProduct->discount_price,
                                'in_stock'           => $sourcePipelineProduct->in_stock,
                                'has_discount_price' => $sourcePipelineProduct->has_discount_price,
                                'is_featured'        => $sourcePipelineProduct->is_featured,
                                'published_at'       => $sourcePipelineProduct->published_at,
                                'short_description'  => $sourcePipelineProduct->short_description,
                                'description'        => $sourcePipelineProduct->description,
                                'source_id'          => $sourcePipelineProduct->id,
                                'updated_at'         => $sourcePipelineProduct->updated_at,
                                'created_at'         => Carbon::now(config('app.timezone')),
                                'import_notices'     => $sourcePipelineProduct->import_notices ?? null,
                            ]);
                            $pipedProductsCount++;
                            if ( ! empty($sourcePipelineProduct->import_notices)) {
                                $productsWithImportNotices[] = $newProduct->_id;
                                $importProductsNoticesCount++;
                            }
                        });
                }
            });

        return [
            'pipedProductsCount'         => $pipedProductsCount,
            'importProductsNoticesCount' => $importProductsNoticesCount,
            'productsWithImportNotices'  => $productsWithImportNotices,
        ];

    }
    // importPipelineProducts(bool $clearTable = false): array


    /**
     * Run import of Products into Product model from PipelineProduct(other db connection) model
     *
     * @param PipelineProduct $pipelineProduct
     * Source table BiCurrencies = ts_products_categories
     *
     * @return array
     */
    public function importPipelineProductCategories(bool $clearTable = false): array
    {
        if ($clearTable) {
            \DB::table((new ProductCategory)->getCollection())->delete();
        }

        $pipedProductCategoriesCount         = 0;
        $sourceProductCategories             = PipelineProductCategory
            ::chunk($this->chunkItemsPerPage, function ($sourceProductCategories) use (
                &$pipedProductCategoriesCount,
            ) {
                foreach ($sourceProductCategories as $index => $sourcePipelineProductCategory) {
                    app(Pipeline::class)
                        ->send($sourcePipelineProductCategory)
                        ->through([
                            //      * Check Category  if there are inserted in prior pipeline Category model
                            FilterOnlyProductCategoriesWithExistingProductCategory::class,
                        ])->then(function ($sourcePipelineProductCategory) use (
                            &$pipedProductCategoriesCount,
                        ) {
                            $product = Product::getBySourceId($sourcePipelineProductCategory->product_id)->first();
                            $category = Category::getBySourceId($sourcePipelineProductCategory->category_id)->first();
                            if(!empty($product) and !empty($category)) {
                                $newProductCategory = ProductCategory::create([
                                    'product_id'  => $product->_id,
                                    'category_id' => $category->_id,
                                    'created_at'  => Carbon::now(config('app.timezone')),
                                ]);
                                $pipedProductCategoriesCount++;
                            }
                        });
                }
            });

        return [
            'pipedProductCategoriesCount'         => $pipedProductCategoriesCount,
        ];

    }

    /**
     * Run import of Products into Product model from PipelineProduct(other db connection) model
     *
     * @param PipelineProduct $pipelineProduct
     * Source table BiCurrencies = ts_products_categories
     *
     * @return array
     */
    public function importPipelineProductCities(bool $clearTable = false): array
    {
        if ($clearTable) {
            \DB::table((new ProductCategory)->getCollection())->delete();
        }

        $pipedProductCategoriesCount         = 0;
        $sourceProductCategories             = PipelineProductCategory
            ::chunk($this->chunkItemsPerPage, function ($sourceProductCategories) use (
                &$pipedProductCategoriesCount,
            ) {
                foreach ($sourceProductCategories as $index => $sourcePipelineProductCategory) {
                    app(Pipeline::class)
                        ->send($sourcePipelineProductCategory)
                        ->through([
                            //      * Check Category  if there are inserted in prior pipeline Category model
                            FilterOnlyProductCategoriesWithExistingProductCategory::class,
                        ])->then(function ($sourcePipelineProductCategory) use (
                            &$pipedProductCategoriesCount,
                        ) {
                            $product = Product::getBySourceId($sourcePipelineProductCategory->product_id)->first();
                            $category = Category::getBySourceId($sourcePipelineProductCategory->category_id)->first();
                            if(!empty($product) and !empty($category)) {
                                $newProductCategory = ProductCategory::create([
                                    'product_id'  => $product->_id,
                                    'category_id' => $category->_id,
                                    'created_at'  => Carbon::now(config('app.timezone')),
                                ]);
                                $pipedProductCategoriesCount++;
                            }
                        });
                }
            });

        return [
            'pipedProductCategoriesCount'         => $pipedProductCategoriesCount,
        ];

    }

}
