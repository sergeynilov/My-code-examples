<?php
namespace App\Repositories;

use App\Enums\ActionHistoryType;
use App\Enums\CheckValueType;
use App\Models\Subscription;
use App\Models\Product;
use App\Models\ProductSubscription;
use App\Models\Settings;
use Carbon\Carbon;
use Exception;
use App\Library\Services\Interfaces\ActionsHistoryInterface;
use App\Repositories\Interfaces\ItemBulkOperationsInterface;
use App;
use DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductItemBulkOperationsRepository implements ItemBulkOperationsInterface
{

    /**
     * Activate(SET status = Product::STATUS_ACTIVE) collection of Products by ids array
     *
     * @param array $items - array of Products by id
     *
     * @return array : activatedProductsCount - total number of activated products,
     * skippedAsAlreadyActivatedProductsCount - number of  skipped as already activated products,
     * label -  text for output report,
     *
     */
    public function activateItems(array $items): array
    {
        $activatedProductsCount                 = 0;
        $skippedAsAlreadyActivatedProductsCount = 0;
        Product
            ::getById($items)
            ->get()
            ->map(function ($productItem) use (&$activatedProductsCount, &$skippedAsAlreadyActivatedProductsCount) {
                if ($productItem->status === Product::STATUS_ACTIVE) {
                    $skippedAsAlreadyActivatedProductsCount++;

                    return false;
                }
                $productItem->status     = Product::STATUS_ACTIVE;
                $productItem->updated_at = Carbon::now(config('app.timezone'));
                $productItem->save();
                $activatedProductsCount++;

                return $productItem;
            });

        return [
            'activatedProductsCount'                 => $activatedProductsCount,
            'skippedAsAlreadyActivatedProductsCount' => $skippedAsAlreadyActivatedProductsCount,
            'label'                                  => 'activated',
        ];

    }

    /**
     * Deactivate(SET status = Product::STATUS_ACTIVE) collection of Products by ids array
     *
     * @param array $items - array of Products by id
     *
     * @return array : deactivatedProductsCount - total number of deactivated products,
     * skippedAsAlreadyDeactivatedProductsCount - number of  skipped as already deactivated products,
     * label -  text for output report,
     *
     */
    public function deactivateItems(array $items): array
    {
        $deactivatedProductsCount                 = 0;
        $skippedAsAlreadyDeactivatedProductsCount = 0;
        Product
            ::getById($items)
            ->get()
            ->map(function ($productItem) use (&$deactivatedProductsCount, &$skippedAsAlreadyDeactivatedProductsCount) {
                if ($productItem->status === Product::STATUS_INACTIVE) {
                    $skippedAsAlreadyDeactivatedProductsCount++;

                    return false;
                }
                $productItem->status     = Product::STATUS_INACTIVE;
                $productItem->updated_at = Carbon::now(config('app.timezone'));
                $productItem->save();
                $deactivatedProductsCount++;

                return $productItem;
            });

        return [
            'deactivatedProductsCount'                 => $deactivatedProductsCount,
            'skippedAsAlreadyDeactivatedProductsCount' => $skippedAsAlreadyDeactivatedProductsCount,
            'label'                                    => 'deactivated',
        ];

    }

    /**
     * Delete collection of Products by ids array
     *
     * @param array $items - array of Products by id
     *
     * @return array : deletedProductsCount - total number of deleted products,
     * label -  text for output report,
     *
     */
    public function deleteItems(array $items): array
    {
        $deletedProductsCount = 0;
        Product
            ::getById($items)
            ->get()
            ->map(function ($productItem) use (&$deletedProductsCount) {
                $productItem->delete();
                $deletedProductsCount++;

                return false;
            });

        return [
            'deletedProductsCount' => $deletedProductsCount,
            'label'                => 'deleted',
        ];

    }

    public function exportItems(array $items): array
    {
        // It will be implemented later
    }


}

?>
