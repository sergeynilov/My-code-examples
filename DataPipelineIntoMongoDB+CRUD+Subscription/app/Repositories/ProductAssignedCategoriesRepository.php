<?php
namespace App\Repositories;

use App\Enums\ActionHistoryType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;

use App\Library\Services\Interfaces\ActionsHistoryInterface;
use App\Repositories\Interfaces\AssignedItemsInterface;
use App;
use DB;
use Illuminate\Support\Str;

class ProductAssignedCategoriesRepository implements AssignedItemsInterface
{
    /**
     * Returns collection of ProductCategory by $parentId($productId)
     *
     * @param string $parentId - key $productId for filtering
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @return array : totalCategoriesCount - total number of found categories,
     * product - relative product(by $parentId),
     * productAssignedCategories -  collection of found ProductCategory data
     * assignedCategoriesCount - how many categories are already assigned for relative product(by $parentId)
     *
     */
    public function getItems(string $parentId): array
    {
        $product = Product
            ::getById($parentId)
            ->firstOrFail();
        $totalCategoriesCount = Category::count();

        $assignedCategoriesCount   = 0;
        $productAssignedCategories = Category
            ::orderBy('title', 'desc')
            ->get()
            ->map(function ($productAssignedCategoryItem) use ($parentId, &$assignedCategoriesCount) {
                $productAssignedCategoryItem->description             =
                    Str::limit(strip_tags($productAssignedCategoryItem->description),
                        50);
                $productAssignedCategoryItem->active_label            =
                    Category::getActiveLabel($productAssignedCategoryItem->active);
                $productAssignedCategoryItem->category_products_count = ProductCategory
                    ::getByCategoryId($productAssignedCategoryItem->_id)
                    ->count();
                $productAssignedCategoryItem->is_category_assigned    =
                    ProductCategory
                        ::getByProductId($parentId)
                        ->getByCategoryId($productAssignedCategoryItem->_id)
                        ->count() >= 1;
                if ($productAssignedCategoryItem->is_category_assigned) {
                    $assignedCategoriesCount++;
                }
                return $productAssignedCategoryItem;
            });

        return [
            'totalCategoriesCount'      => $totalCategoriesCount,
            'product'                   => $product,
            'productAssignedCategories' => $productAssignedCategories,
            'assignedCategoriesCount'   => $assignedCategoriesCount,
        ];
    }

    public function assignItem(string $parentId, string $itemId): array
    {
        $product = Product
            ::getById($parentId)
            ->firstOrFail();
        $session = DB::getMongoClient()->startSession();
        $session->startTransaction();
        $productCategory = ProductCategory
            ::getByProductId($parentId)
            ->getByCategoryId($itemId)
            ->first();
        if (empty($productCategory)) {
            $productCategory = ProductCategory::create([
                'product_id'  => $parentId,
                'category_id' => $itemId,
            ]);
        }

        $actionsHistory = app(ActionsHistoryInterface::class);
        $actionsHistory->addLog(
            model: $productCategory,
            action_type: ActionHistoryType:: AHT_PRODUCT_SUBSCRIBED_TO_SUBSCRIPTION,
            body: ' Product ' . $parentId . '=>' . $product->name . ' (' . $product->email .
                  ') was subscribed to product ' . $parentId . '=>' . $product->title
        );

        $session->commitTransaction();

        return ['result' => false, 'message' => 'Product was successfully assigned to product'];
    }

    public function revokeItem(string $parentId, string $itemId): array
    {
        $product = Product
            ::getById($parentId)
            ->firstOrFail();
        $productCategory = ProductCategory
            ::getByProductId($parentId)
            ->getByCategoryId($itemId)
            ->firstOrFail();
        $session = DB::getMongoClient()->startSession();
        $session->startTransaction();
        $productCategory->delete();

        $actionsHistory = \App::make(App\Library\Interfaces\ActionsHistoryInterface::class);
        $actionsHistory->addLog(
            model: $productCategory,
            action_type: ActionHistoryType:: AHT_USER_UNSUBSCRIBED_FROM_SUBSCRIPTION,
            body: ' Product ' . $parentId . '=>' . $product->name . ' (' . $product->email
                  .          ') was unsubscribed from product ' . $parentId . '=>' . $product->title
        );

        $session->commitTransaction();

        return ['result' => true];
    }

}

?>
