<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActionHistoryType;
use App\Repositories\ProductAssignedCategoriesRepository;
use App\Repositories\ProductAssignedSubscriptionsRepository;
use App\Repositories\ProductItemBulkOperationsRepository;
use App\Repositories\ProductCrudRepository;
use App\Exceptions\UploadedImageHasInvalidProps;
use App\Models\{Product, Category};

use App\Http\Requests\ProductRequest;
use App\Library\{ReportProduct, PageIntoFile};
use App\Enums\ProductBulkOperation;

use Carbon\Carbon;
use Exception;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Library\Services\Interfaces\UploadedFileManagementInterface;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    private $imageFieldName = 'image';
    protected $productCrudRepository;
    protected $productAssignedCategoriesRepository;
    protected $productAssignedSubscriptionsRepository;
    protected $productItemBulkOperationsRepository;

    public function __construct(
        ProductCrudRepository $productCrudRepository,
        ProductAssignedCategoriesRepository $productAssignedCategoriesRepository,
        ProductAssignedSubscriptionsRepository $productAssignedSubscriptionsRepository,
        ProductItemBulkOperationsRepository $productItemBulkOperationsRepository,
    ) {
        $this->productCrudRepository                  = $productCrudRepository;
        $this->productAssignedCategoriesRepository    = $productAssignedCategoriesRepository;
        $this->productAssignedSubscriptionsRepository = $productAssignedSubscriptionsRepository;
        $this->productItemBulkOperationsRepository    = $productItemBulkOperationsRepository;
        $this->middleware('FilterDataOnSave:description,short_description')->only('store', 'update');
    }


    /*  PRODUCT CRUD(implements CrudInterface) BLOCK START */

    /**
     * Display a container for listing of the products from storage
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $request               = request();
        $inStockSelectionItems = Product::getInStockSelectionItems(true, [Product::IN_STOCK_YES]);

        return view('admin/products/index', [
            'filter_status'                  => '',
            'filter_title'                   => '',
            'mode'                           => $request->mode,
            'statusSelectionItems'           => Product::getStatusSelectionItems(),
            'hasDiscountPriceSelectionItems' => Product::getHasDiscountPriceSelectionItems(true,
                [/*Product::HAS_DISCOUNT_PRICE_YES*/]),
            'inStockSelectionItems'          => $inStockSelectionItems,
            'havingImportSelectionItems'     => Product::getHavingImportNoticeSelectionItems(true),
        ]);
    }

    /**
     * Returns (filtered) paginated collection of Products
     *
     * @param string $page - paginated page, if empty - all data would be returned
     *
     * @param string $sortedBy - how data are sorted, can be combination of fields
     *
     * @param string $filters - how data are filtered, keys : active(0/1), name - string, import_notice - string
     *
     * returns filtered data / total rows number / pagination per page
     *
     * @return array : categories - collection of found data,
     * totalCategoriesCount - total number of found Categories,
     * paginationPerPage - number of rows in 1 paginated page
     */
    public function filter(): array
    {
        $request = request();
        return $this->productCrudRepository->filter(
            page: $request->page ?? 1,
            filters: $request->only('status', 'title', 'in_stock', 'from_regular_price', 'till_regular_price',
            'has_discount_price', 'from_discount_price', 'till_discount_price', 'import_notice'),
            sortedBy: $request->sorted_by ?? '',
        );
    }

    /**
     * Show the form for creating a new product.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('admin/products/create', [
            'statusSelectionItems'  => Product::getStatusSelectionItems(),
            'inStockSelectionItems' => Product::getInStockSelectionItems(),
        ]);
    }

    /**
     * Store new validated product in storage.
     *
     * @return \Illuminate\Routing\Redirector | \Illuminate\Http\RedirectResponse
     */
    public function store(ProductRequest $request)
    {
        try {
            $session = DB::getMongoClient()->startSession();
            $session->startTransaction([]);
            $product        = $this->productCrudRepository->store($request->all());

            $session->commitTransaction();
        } catch (Exception $e) {
            $session->abortTransaction();
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.products.edit', $product->_id))
            ->with('message', 'New product was successfully added')
            ->with('message_type', 'success');
    }

    /**
     * Show the form for editing the specified Product model.
     *
     * @param string $productId
     *
     * @return \Illuminate\Contracts\View\View | \Illuminate\Routing\Redirector
     */
    public function edit(string $productId, UploadedFileManagementInterface $uploadedFileManagement)
    {
        try {
            $product = $this->productCrudRepository->get($productId);
        } catch (ModelNotFoundException $e) {
            return redirect(route('admin.products.index'))
                ->with('message', 'Product "' . $productId . '" not found.')
                ->with('message_type', 'error');
        }

        $productImgProps = $uploadedFileManagement->getImageFileDetails(
            itemId: $product->_id,
            imagesUploadDirectory: Product::getUploadsProductDir(),
            imageFilename: $product->image,
            skipNonExistingFile: true);

        return view('admin/products/edit', [
            'statusSelectionItems'         => Product::getStatusSelectionItems(),
            'categoryActiveSelectionItems' => Category::getActiveSelectionItems(),
            'product'                      => $product,
            'productImgProps'              => $productImgProps
        ]);
    }

    /**
     * Update validated product in storage.
     *
     * @param ProductRequest $request - request with put data to update and validate product in storage
     *
     * @param string $productId
     *
     * @return \Illuminate\Routing\Redirector | \Illuminate\Http\RedirectResponse'
     */
    public function update(ProductRequest $request, string $productId)
    {
        try {
            $product = $this->productCrudRepository->get($productId);
        } catch (ModelNotFoundException $e) {
            return redirect(route('admin.products.index'))
                ->with('message', 'Product "' . $productId . '" not found.')
                ->with('message_type', 'error');
        }
        try {

            $session = DB::getMongoClient()->startSession();
            $session->startTransaction();
            $success = $this->productCrudRepository->update($product, $request->all());
            $session->commitTransaction();
        } catch (Exception $e) {
            $session->abortTransaction();

            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.products.index'))
            ->with('message', 'Product was successfully updated')
            ->with('message_type', 'success');
    }

    /**
     * Remove the specified Product model from storage.
     *
     * @param string $productId
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function destroy(string $productId)
    {
        try {
            $product = $this->productCrudRepository->get($productId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product # "' . $productId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
        try {
            $this->productCrudRepository->delete($product);
        } catch (Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->noContent();
    }

    /*  PRODUCT CRUD(implements CrudInterface) BLOCK END */


    /*  PRODUCT CRUD(implements UploadedImageInterface) BLOCK Start */
    /**
     * Upload image in storage under relative path storage/app/public/products/-product-ID/image_name.ext
     *
     * @param Request $request - request with uploaded file
     *
     * @return \Illuminate\Http\JsonResponse with keys on success, product - relative product, productImgProps -
     * properties of uploaded file
     */
    public function imageUpload(Request $request)
    {
        try {
            $imageUploadSuccess = $this->productCrudRepository->imageUpload(
                request: $request,
                imageFieldName: $this->imageFieldName,
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => 'Product # "' . ($request->id ?? null) . '" not found',
            ], HTTP_RESPONSE_BAD_REQUEST);
        } catch (UploadedImageHasInvalidProps $e) {
            return response()->json(['errors' => $e->getMessage()], HTTP_RESPONSE_BAD_REQUEST);
        }

        return response()->json([
            'product'         => $imageUploadSuccess['product'],
            'productImgProps' => $imageUploadSuccess['productImgProps']
        ], HTTP_RESPONSE_OK);
    }

    /**
     * Remove image from storage under relative path storage/app/public/models/model-ID/image_name.ext by product Id
     *
     * @param string $id - product Id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function imageClear($productId)
    {
        try {
            $this->productCrudRepository->imageClear(id: $productId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'errors' => 'Product # "' . ($productId ?? null) . '" not found',
            ], HTTP_RESPONSE_BAD_REQUEST);
        } catch (UploadedImageHasInvalidProps $e) {
            return response()->json(['errors' => $e->getMessage()], HTTP_RESPONSE_BAD_REQUEST);
        }
        response()->noContent();
    }

    /*  PRODUCT CRUD(implements UploadedImageInterface) BLOCK END */


    /*  PRODUCT ASSIGNED CATEGORIES(implements ProductAssignedCategoriesRepository) BLOCK Start */

    public function categoriesGetItems(string $productId)
    {
        try {
            return $this->productAssignedCategoriesRepository->getItems(
                parentId: $productId,
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product # "' . $productId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
    }

    public function categoriesAssignItem(string $productId, string $categoryId)
    {
        try {
            return $this->productAssignedCategoriesRepository->assignItem(
                parentId: $productId,
                itemId: $categoryId,
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product # "' . $productId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
    }

    public function categoriesRevokeItem(string $productId, string $categoryId)
    {
        try {
            return $this->productAssignedCategoriesRepository->revokeItem(
                parentId: $productId,
                itemId: $categoryId,
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product # "' . $productId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
    }
    /*  PRODUCT ASSIGNED CATEGORIES(implements ProductAssignedCategoriesRepository) BLOCK End */


    /*  PRODUCT ASSIGNED SUBSCRIPTIONS(implements ProductAssignedSubscriptionsRepository) BLOCK Start */

    public function subscriptionsGetItems(string $productId)
    {
        try {
            return $this->productAssignedSubscriptionsRepository->getItems(
                parentId: $productId,
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product # "' . $productId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
    }

    public function subscriptionsAssignItem(string $productId, string $subscriptionId)
    {
        try {
            return $this->productAssignedSubscriptionsRepository->assignItem(
                parentId: $productId,
                itemId: $subscriptionId,
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product # "' . $productId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
    }

    public function subscriptionsRevokeItem(string $productId, string $subscriptionId)
    {
        try {
            return $this->productAssignedSubscriptionsRepository->revokeItem(
                parentId: $productId,
                itemId: $subscriptionId,
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product # "' . $productId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
    }

    /*  PRODUCT ASSIGNED SUBSCRIPTIONS(implements ProductAssignedSubscriptionsRepository) BLOCK End */


    /*  PRODUCT ITEM_BULK_OPERATIONS (implements ProductItemBulkOperationsRepository) BLOCK Start */

    public function runBulkOperation()
    {
        $request            = request();
        $bulkOperation      = $request->bulk_operation ?? '';
        $selectedProductIds = $request->selected_product_ids ?? [];
        try {
            if ($bulkOperation === ProductBulkOperation::PBO_ACTIVATE) {
                return $this->productItemBulkOperationsRepository->activateItems($selectedProductIds);
            }
            if ($bulkOperation === ProductBulkOperation::PBO_DEACTIVATE) {
                return $this->productItemBulkOperationsRepository->deactivateItems($selectedProductIds);
            }
            if ($bulkOperation === ProductBulkOperation::PBO_DELETE) {
                return $this->productItemBulkOperationsRepository->deleteItems($selectedProductIds);
            }
            if ($bulkOperation === ProductBulkOperation::PBO_EXPORT) {
                return $this->productItemBulkOperationsRepository->exportItems($selectedProductIds);
            }
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /*  PRODUCT ITEM_BULK_OPERATIONS (implements ProductItemBulkOperationsRepository) BLOCK End */


    /**
     * Clear import_notices from Product model.
     *
     * @param string $productId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function importNoticesClear($productId)
    {
        $product = Product::find($productId);
        if ($product === null) {
            return response()->json(
                ['message' => 'Product # "' . $productId . '" not found!'],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }
        try {
            $product->import_notices = null;
            $product->updated_at     = Carbon::now(config('app.timezone'));
            $product->save();
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json(null, HTTP_RESPONSE_OK_RESOURCE_UPDATED);
    }

    /**
     * Show the form for product report with specified resource.
     *
     * @param string $productId
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function showReport(
        string $productId
    ) {
        return view('admin/products/report', [
            'productId' => $productId,
        ]);
    }

    /**
     * Generate Pdf report by content in request
     *
     * @param Request request()
     *
     * @return Request
     */
    public function generatePdfByContent()
    {
        $pageIntoFile = new PageIntoFile(request()->all());
        $success      = $pageIntoFile->generate(true);
        return $success;
    }


}
