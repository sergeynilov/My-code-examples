<?php
namespace App\Repositories;

use App\Enums\ActionHistoryType;
use App\Models\Subscription;
use App\Models\Product;
use App\Models\ProductSubscription;
use App\Library\Services\Interfaces\ActionsHistoryInterface;
use App\Repositories\Interfaces\AssignedItemsInterface;
use App;
use DB;
use Illuminate\Support\Str;

class ProductAssignedSubscriptionsRepository implements AssignedItemsInterface
{

    public function getItems(string $parentId): array
    {
        $product              = Product
            ::getById($parentId)
            ->firstOrFail();
        $totalSubscriptionsCount = Subscription::count();

        $assignedSubscriptionsCount   = 0;
        $productAssignedSubscriptions = Subscription
            ::orderBy('title', 'desc')
            ->get()
            ->map(function ($productAssignedSubscriptionItem) use ($parentId, &$assignedSubscriptionsCount) {
                $productAssignedSubscriptionItem->description             =
                    Str::limit(strip_tags($productAssignedSubscriptionItem->description),
                        50);
                $productAssignedSubscriptionItem->published_label            =
                    Subscription::getPublishedLabel($productAssignedSubscriptionItem->published);
                $productAssignedSubscriptionItem->subscription_products_count = ProductSubscription
                    ::getBySubscriptionId($productAssignedSubscriptionItem->_id)
                    ->count();
                $productAssignedSubscriptionItem->is_subscription_assigned    =
                    ProductSubscription
                        ::getByProductId($parentId)
                        ->getBySubscriptionId($productAssignedSubscriptionItem->_id)
                        ->count() >= 1;
                if ($productAssignedSubscriptionItem->is_subscription_assigned) {
                    $assignedSubscriptionsCount++;
                }

                return $productAssignedSubscriptionItem;
            });

        return [
            'totalSubscriptionsCount'      => $totalSubscriptionsCount,
            'product'                   => $product,
            'productAssignedSubscriptions' => $productAssignedSubscriptions,

            'assignedSubscriptionsCount'   => $assignedSubscriptionsCount,
        ];
    }

    public function assignItem(string $parentId, string $itemId): array
    {
        $product = Product
            ::getById($parentId)
            ->firstOrFail();
        $session = DB::getMongoClient()->startSession();
        $session->startTransaction();
        $productSubscription = ProductSubscription
            ::getByProductId($parentId)
            ->getBySubscriptionId($itemId)
            ->first();
        if (empty($productSubscription)) {
            $productSubscription = ProductSubscription::create([
                'product_id'  => $parentId,
                'subscription_id' => $itemId,
            ]);
        }

        $actionsHistory = app(ActionsHistoryInterface::class);
        $actionsHistory->addLog(
            model: $productSubscription,
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
        $productSubscription = ProductSubscription
            ::getByProductId($parentId)
            ->getBySubscriptionId($itemId)
            ->firstOrFail();
        $session = DB::getMongoClient()->startSession();
        $session->startTransaction();
        $productSubscription->delete();

        $actionsHistory = \App::make(App\Library\Services\Interfaces\ActionsHistoryInterface::class);
        $actionsHistory->addLog(
            model: $productSubscription,
            action_type: ActionHistoryType:: AHT_USER_UNSUBSCRIBED_FROM_SUBSCRIPTION,
            body: ' Product ' . $parentId . '=>' . $product->name . ' (' . $product->email
                  .          ') was unsubscribed from product ' . $parentId . '=>' . $product->title
        );

        $session->commitTransaction();

        return ['result' => true];
    }

}

?>
