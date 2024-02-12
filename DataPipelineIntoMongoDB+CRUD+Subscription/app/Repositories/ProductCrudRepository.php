<?php
namespace App\Repositories;

use App\Enums\CheckValueType;
use App\Enums\UploadImageRules;
use App\Library\Services\Interfaces\UploadedFileManagementInterface;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCity;
use App\Models\ProductSubscription;
use App\Models\Settings;
use App\Repositories\Interfaces\CrudInterface;
use App\Repositories\Interfaces\UploadedImageInterface;
use Carbon\Carbon;
use DateConv;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Exceptions\UploadedImageHasInvalidProps;

class ProductCrudRepository implements CrudInterface, UploadedImageInterface
{

    /*  PRODUCT CRUD(implements CrudInterface) BLOCK START */

    /**
     * Returns (filtered) paginated collection of Products
     *
     * @param string $page - paginated page, if empty - all data would be returned
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @param string $filters - how data are filtered, keys : status, title, in_stock, from_regular_price - decimal,
     * till_regular_price - decimal, has_discount_price - decimal, from_discount_price - decimal,
     * till_discount_price - decimal, import_notice - string
     *
     * returns a filtered data / total rows number / pagination per page
     *
     * @return array : products - collection of found data,
     * totalProductsCount - total number of found products,
     * paginationPerPage - number of rows in 1 paginated page
     */
    public function filter(int $page = 1, string $sortedBy = '', array $filters = []): array
    {
        $paginationPerPage = Settings::getValue('pagination_per_page', CheckValueType::cvtInteger, 20);

        $page                   = $filters['page'] ?? 1;
        $filterStatus           = $filters['status'] ?? '';
        $filterTitle            = $filters['title'] ?? '';
        $filterInStock          = $filters['in_stock'] ?? '';
        $filterFromRegularPrice = $filters['from_regular_price'] ?? null;
        $filterTillRegularPrice = $filters['till_regular_price'] ?? null;

        $filterFromDiscountPrice = $filters['from_discount_price'] ?? null;
        $filterTillDiscountPrice = $filters['till_discount_price'] ?? null;
        $filterImportNotice      = $filters['import_notice'] ?? null;

        $rowsSortedBy = $filters['sorted_by'] ?? null;
        $sortByField            = 'status';
        $sortOrdering           = 'desc';
        $additive_sort_by_field = 'title';
        $additive_sort_ordering = 'asc';
        if ($rowsSortedBy == 'status_title') {
            $sortByField            = 'status';
            $sortOrdering           = 'desc';
            $additive_sort_by_field = 'title';
            $additive_sort_ordering = 'asc';
        }
        if ($rowsSortedBy == 'status_in_stock') {
            $sortByField            = 'status';
            $sortOrdering           = 'desc';
            $additive_sort_by_field = 'in_stock';
            $additive_sort_ordering = 'asc';
        }
        if ($rowsSortedBy == 'in_stock_regular_price') {
            $sortByField            = 'in_stock';
            $sortOrdering           = 'desc';
            $additive_sort_by_field = 'regular_price';
            $additive_sort_ordering = 'asc';
        }
        if ($rowsSortedBy == 'related_users_count') {
            $sortByField            = 'user_products_count';
            $sortOrdering           = 'asc';
            $additive_sort_by_field = 'title';
            $additive_sort_ordering = 'asc';
        }

        if ($rowsSortedBy == 'last_created') {
            $sortByField            = 'created_at';
            $sortOrdering           = 'desc';
            $additive_sort_by_field = 'id';
            $additive_sort_ordering = 'desc';
        }

        $totalProductsCount = Product
            ::getByTitle($filterTitle)
            ->getByStatus($filterStatus)
            ->getByInStock((bool)$filterInStock)
            ->getByRegularPriceFrom((float)$filterFromRegularPrice)
            ->getByRegularPriceTill((float)$filterTillRegularPrice)
            ->getByDiscountPrice((float)$filterFromDiscountPrice, '>=')
            ->getByDiscountPrice((float)$filterTillDiscountPrice, '<=')
            ->onlyWithImportNoticesNoneEmpty($filterImportNotice)
            ->count();
        $products = Product
            ::getByTitle($filterTitle)
            ->getByStatus($filterStatus)
            ->getByInStock((bool)$filterInStock)
            ->getByRegularPriceFrom((float)$filterFromRegularPrice)
            ->getByRegularPriceTill((float)$filterTillRegularPrice)
            ->getByDiscountPrice((float)$filterFromDiscountPrice, '>=')
            ->getByDiscountPrice((float)$filterTillDiscountPrice, '<=')
            ->onlyWithImportNoticesNoneEmpty($filterImportNotice)
            ->orderBy($sortByField, $sortOrdering)
            ->orderBy($additive_sort_by_field, $additive_sort_ordering)
            ->paginate($paginationPerPage, array('*'), 'page', $page)
            ->through(function ($productItem) {
                $productItem->created_at_formatted     = DateConv::getFormattedDateTime($productItem->created_at);
                $productItem->title                    = Str::limit($productItem->title, 30, ' ...');
                $productItem->regular_price_formatted  = formatCurrencySum($productItem->regular_price);
                $productItem->discount_price_formatted = formatCurrencySum($productItem->discount_price);
                $productItem->status_label             = Product::getStatusLabel($productItem->status);
                $productItem->in_stock_label           = Product::getInStockLabel($productItem->in_stock);
                $productItem->product_categories_count = ProductCategory
                    ::getByProductId($productItem->_id)
                    ->count();
                $productItem->product_cities_count     = ProductCity
                    ::getByProductId($productItem->_id)
                    ->count();

                $productItem->product_subscriptions_count = ProductSubscription
                    ::getByProductId($productItem->_id)
                    ->count();

                return $productItem;
            });

        return [
            'productsIds'        => $products->pluck('id'),
            'products'           => $products,
            'totalProductsCount' => $totalProductsCount,
            'paginationPerPage'  => $paginationPerPage,
        ];
    }

    /**
     * Get an individual Product model by id
     *
     * @param string $id
     *
     * @return Model
     */
    public function get(string $id): Model
    {
        return Product
            ::getById($id)
            ->firstOrFail();
    }

    /**
     * Store new validated Product model in storage
     *
     * @return Model
     */
    public function store(array $data): Model
    {
        $data['in_stock']           = ! empty($data['in_stock']);
        $data['has_discount_price'] = ! empty($data['has_discount_price']);
        $data['is_featured']        = ! empty($data['is_featured']);

        return Product::create([
            'title'              => $data['title'],
            'status'             => $data['status'],
            'regular_price'      => $data['regular_price'],
            'discount_price'     => $data['discount_price'],
            'in_stock'           => $data['in_stock'],
            'has_discount_price' => $data['has_discount_price'],
            'is_featured'        => $data['is_featured'],
            'short_description'  => $data['short_description'],
            'description'        => $data['description'],
            'published_at'       => $data['published_at'] ?? null,
            'image'              => $data['image'] ?? null,
        ]);
    }

    /**
     * Update validated Product model with given array in storage
     *
     * @param string $productId
     *
     * @param array $data
     *
     * @return bool - if update was succesfull
     */
    public function update(Model $model, array $data): bool
    {
        $data['in_stock']           = ! empty($data['in_stock']);
        $data['has_discount_price'] = ! empty($data['has_discount_price']);
        $data['is_featured']        = ! empty($data['is_featured']);

        return $model->update($data);
    }

    /**
     * Remove the specified Product model from storage
     *
     * @param Product model $model
     *
     * @return void
     */
    public function delete(Model $model): void
    {
        $model->delete();
    }

    /*  PRODUCT CRUD(implements CrudInterface) BLOCK END */


    /*  PRODUCT CRUD(implements UploadedImageInterface) BLOCK Start */

    /**
     * Upload image in storage under relative path storage/app/public/models/model-ID/image_name.ext
     *
     * @param Request $request - request with uploaded file
     *
     * @param string $imageFieldName - image key in request with uploaded file
     *
     * @return array :    result === 1 if upload was successfull, uploadedImagePath - relative path of uploaded file
     * under storage, imageName - name  of uploaded file
     */
    public function imageUpload(Request $request, string $imageFieldName): array
    {
        $requestData            = $request->all();
        $product                = Product::findOrFail($requestData['id']);
        $uploadedFileManagement = app(UploadedFileManagementInterface::class);
        $uploadedFileManagement->setRequest($request);
        $uploadedFileManagement->setImageFieldName($imageFieldName);
        $validated = $uploadedFileManagement->validate(UploadImageRules::UIR_PRODUCT_IMAGE);
        $productImgProps = [];

        if ( ! $validated['result']) {
            throw new UploadedImageHasInvalidProps($validated['message']);
        }
        if ($validated['result']) {
            $uploadedRet = $uploadedFileManagement->upload(Product::getProductDir($product->_id));
            if ($uploadedRet['result']) {
                $product->image      = $uploadedRet['imageName'];
                $product->updated_at = Carbon::now(config('app.timezone'));
                $product->save();
                $productImgProps = $uploadedFileManagement->getImageFileDetails(
                    itemId: $product->_id,
                    imagesUploadDirectory: Product::getUploadsProductDir(),
                    imageFilename: $product->image,
                    skipNonExistingFile: true);
            } else {
                return [
                    'result'          => false,
                    'message'         => 'Error uploading image ' . $uploadedRet['message'] ?? '',
                    'productImgProps' => []
                ];
            }

        }

        return [
            'result'          => true,
            'product'         => $product,
            'productImgProps' => $productImgProps
        ];
    }

    /**
     * Remove image from storage under relative path storage/app/public/models/model-ID/image_name.ext by product Id
     *
     * @param string $id - product Id
     *
     * @return void
     */
    public function imageClear(string $id): void
    {
        $product = Product::findOrFail($id);
        $uploadedFileManagement = app(UploadedFileManagementInterface::class);
        $uploadedFileManagement->remove(
            relativeFilePath: Product::getProductImagePath($id, $product->image),
        );
        $product->image      = null;
        $product->updated_at = Carbon::now(config('app.timezone'));
        $product->save();
    }

    /*  PRODUCT CRUD(implements UploadedImageInterface) BLOCK END */

}

?>
