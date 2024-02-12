<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use App\Models\{User, Category};
use App\Http\Requests\CategoryRequest;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\CategoryCrudRepository;

class CategoryController extends Controller
{
    protected $categoryCrudRepository;

    public function __construct(CategoryCrudRepository $categoryCrudRepository)
    {
        $this->categoryCrudRepository = $categoryCrudRepository;
        $this->middleware('FilterDataOnSave:text')->only('store', 'update');
    }

    /**
     * Display a container for listing of the categories from storage
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $request = request();
        return view('admin/categories/index', [
            'filter_active'              => '',
            'filter_name'                => '',
            'mode'                       => $request->mode,
            'activeSelectionItems'       => Category::getActiveSelectionItems(),
            'havingImportSelectionItems' => Category::getHavingImportNoticeSelectionItems(keyReturn: true,
                subsetItems: [Category::IMPORT_NOTICE_HAVING_IMPORT_NOTICES]),
        ]);
    }

    /**
     * @param string $page - page of pagination, if empty - all data would be returned
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
        $request= request();

        return $this->categoryCrudRepository->filter(
            page: $request->page ?? 1,
            filters: $request->only('active', 'name', 'import_notice'),
            sortedBy: $request->sorted_by ?? '',
        );
    }

    /**
     * Show the form for creating a new category.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('admin/categories/create', []);
    }

    /**
     * Store new validated category in storage.
     *
     * @return \Illuminate\Routing\Redirector | \Illuminate\Http\RedirectResponse
     */
    public function store(CategoryRequest $request)
    {
        try {
            $category = $this->categoryCrudRepository->store($request->all());
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.categories.edit', $category->_id))
            ->with('message', 'New category was successfully added')
            ->with('message_type', 'success');
    }

    /**
     * Show the form for editing the specified Category model.
     *
     * @param string $categoryId
     *
     * @return \Illuminate\Contracts\View\View | \Illuminate\Routing\Redirector
     */
    public function edit(string $categoryId)
    {
        try {
            $category = $this->categoryCrudRepository->get($categoryId);
        } catch (ModelNotFoundException $e) {
            return redirect(route('admin.categories.index'))
                ->with('message', 'Category "' . $categoryId . '" not found.')
                ->with('message_type', 'error');
        }

        return view('admin/categories/edit', [
            'category'                     => $category,
            'interestStatusSelectionItems' => User::getInterestStatusSelectionItems(),
        ]);
    }

    /**
     * Update validated category in storage.
     *
     * @param CategoryRequest $request - request with put data to update and validate category in storage
     *
     * @param string $categoryId
     *
     * @return \Illuminate\Routing\Redirector | \Illuminate\Http\RedirectResponse'
     */
    public function update(CategoryRequest $request, string $categoryId)
    {
        try {
            $category = $this->categoryCrudRepository->get($categoryId);
        } catch (ModelNotFoundException $e) {
            return redirect(route('admin.categories.index'))
                ->with('message', 'Category "' . $categoryId . '" not found.')
                ->with('message_type', 'error');
        }
        try {
            $this->categoryCrudRepository->update($category, $request->all());
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect(route('admin.categories.index'))
            ->with('message', 'Category was successfully updated')
            ->with('message_type', 'success');
    }

    /**
     * Remove the specified Category model from storage.
     *
     * @param string $categoryId
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function destroy(string $categoryId)
    {
        try {
            $category = $this->categoryCrudRepository->get($categoryId);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Category # "' . $categoryId . '" not found',
            ], HTTP_RESPONSE_NOT_FOUND);
        }
        try {
            $this->categoryCrudRepository->delete($category);
        } catch (Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->noContent();
    }

    /**
     * Clear import_notices from Category model.
     *
     * @param string $categoryId - id of Category model
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function importNoticesClear($categoryId)
    {
        $category = Category::find($categoryId);
        if ($category === null) {
            return response()->json(
                ['message' => 'Category # "' . $categoryId . '" not found!'],
                HTTP_RESPONSE_BAD_REQUEST
            );
        }
        try {
            $category->import_notices = null;
            $category->updated_at    = Carbon::now(config('app.timezone'));
            $category->save();
        } catch (Exception $e) {

            return response()->json(['message' => $e->getMessage()], HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

        return response()->json(null, HTTP_RESPONSE_OK_RESOURCE_UPDATED);
    }

}
