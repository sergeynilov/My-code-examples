<?php

namespace App\Library\Services;

use App\Enums\CheckValueType;
use App\Http\Resources\PageResource;
use DB;
use Auth;
use Illuminate\Http\UploadedFile;

use App\Models\Page;
use App\Models\Settings;
use App\Models\PageCategory;
use App\Models\PageRevision;

use Carbon\Carbon;
use App\Library\Services\PageMethodsServiceInterface;
use Illuminate\Http\Request;
use App\Library\Rules\RulesPageUpdate;
use App\Enums\UpdatePageRules;
use Exception;
use Error;

class PageMethods implements PageMethodsServiceInterface
{
    protected bool $checkActiveUser = true;
    /**
     * Display listing of the Page models based on provided filters
     * (filter_title, filter_published, filter_is_homepage).
     *
     * @return \Illuminate\Http\Resources\Json\ResourceCollection
     */
    public function filter(Request $request): \Illuminate\Http\Resources\Json\ResourceCollection
    {

        $filterTitle      = $request->filter_title ?? '';
        $filterPublished  = $request->filter_published ?? '';
        $filterIsHomepage = $request->filter_is_homepage ?? '';
        $perPage          = $request->per_page ?? Settings::getValue('rows_per_page', CheckValueType::cvtInteger, 20);
        $page             = (int)($request->page ?? 1);

        $totalPagesCount = Page
            ::getByTitle($filterTitle, true)
            ->getByIsHomepage($filterIsHomepage)
            ->getByPublished($filterPublished)
            ->count();
        if ( ! empty($perPage) and $perPage > 0) { // get only 1 page by $page
            $pages = Page
                ::getByTitle($filterTitle, true)
                ->getByIsHomepage($filterIsHomepage)
                ->getByPublished($filterPublished)
                ->with('category')
                ->with('pageRevisions')
                ->select('*')
                ->paginate($perPage, null, null, $page);
        } else { // get all pages
            $pages = Page
                ::getByTitle($filterTitle, true)
                ->getByIsHomepage($filterIsHomepage)
                ->getByPublished($filterPublished)
                ->with('category')
                ->with('pageRevisions')
                ->get();
        }

        return (PageResource::collection($pages))
        ->additional([
            'meta' => [
                'total_pages_count' => $totalPagesCount,
                'per_page'          => $perPage,
                'page'              => $page,
                'version'           => getAppVersion()
            ]
        ]);

    }

    /**
     * Store a newly created Page model in db.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(
        array $requestData,
        UploadedFile|string $pageUploadedImageFile = null,
        UploadedFile|string $pageUploadedDocumentFile = null,
        bool $makeValidation = false
    ): \Illuminate\Http\JsonResponse|\Illuminate\Support\MessageBag {

        $loggedUser = Auth::user();
        if ($makeValidation) {
            $pageValidationRulesArray = Page::getPageValidationRulesArray(
                pageId: $requestData['id'] ?? null, creatorId: $requestData['creator_id'] ?? Auth::user()?->id);
            $validator = \Illuminate\Support\Facades\Validator::make($requestData, $pageValidationRulesArray,
                Page::getValidationMessagesArray());
            if ($validator->fails()) {
                $errorMsg = $validator->getMessageBag();
                if (\App::runningInConsole()) {
                    echo '::$errorMsg::' . print_r($errorMsg, true) . '</pre>';
                }

                return $errorMsg;
            }
        } // if ($makeValidation) {

        DB::beginTransaction();
        try {

            $loggedUser = Auth::user();
            $rulesPageUpdate = new RulesPageUpdate(actionLabel: "store", id: null, page : null, loggedUser: $loggedUser);
            $retResults = $rulesPageUpdate->check( rulesToValidate:
                [
                    UpdatePageRules::UPR_LOGGED_USER_HAS_NOT_ACTIVE_STATUS_FOR_ACTION,
                ],
                checkActiveUser: $this->checkActiveUser
            );
            if(!$retResults['result']) {
                return response()->json($retResults['message'], $retResults['returnCode']);
            }

            $metaKeywords = $requestData['meta_keywords'] ?? '';
            if (gettype($metaKeywords) == 'string') {
                $metaKeywords = pregSplit(preg_quote('/;/'), $metaKeywords);
            }
            $requestData['meta_keywords'] = json_encode($metaKeywords);

            if ( Auth::user() and (! Auth::user()->can(ACCESS_PERMISSION_ADMIN_LABEL) and ! Auth::user()->can(ACCESS_PERMISSION_MANAGER_LABEL))) {   // Not Admin or NOT Manager can store only pages with published = false
                $requestData['published'] = false;
            }

            $page = Page::create([
                'title'            => $requestData['title'],
                'content'          => $requestData['content'],
                'content_shortly'  => $requestData['content_shortly'],
                'is_homepage'      => ! empty($requestData['is_homepage']),
                'published'        => ! empty($requestData['published']),
                'meta_description' => $requestData['meta_description'],
                'meta_keywords'    => $requestData['meta_keywords'],
                'creator_id'       => $requestData['creator_id']
            ]);

            $this->savePageCategories(page: $page, pageCategories: $requestData['page_categories'] ?? '', addPage:
                true);

            $pageImageUpload = new ModelImageUpload($page, 'LocalMediaLibrary');
            if(!empty($pageUploadedImageFile)) {
                $pageUploadedImageResource = $pageImageUpload->uploadFile(
                    fileToUpload: $pageUploadedImageFile,
                    requestName: 'image',
                    fileType: 'cover_image',
                    deleteExitingMediaFiles: false
                );
            }
            if(!empty($pageUploadedDocumentFile)) {
                $pageUploadedDocumentResource = $pageImageUpload->uploadFile(
                    fileToUpload: $pageUploadedDocumentFile,
                    requestName: 'document',
                    fileType: 'document',
                    deleteExitingMediaFiles: false
                );
            }

            DB::commit();

            $page = Page
                ::getById($page->id)
                ->with('category')
                ->with('pageRevisions')
                ->first();
            return response()->json(['page' => (new PageResource($page))],
                HTTP_RESPONSE_OK_RESOURCE_CREATED); // 201

        } catch (\Exception $e) {
            DB::rollback();
            if (\App::runningInConsole()) {
                echo $e->getMessage();
            }

            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        } catch (\Error $e) {
            DB::rollback();
            if (\App::runningInConsole()) {
                echo $e->getMessage();
            }

            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Display the specified Page model by page id.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse | PageResource
     */
    public function show(int $id) : \Illuminate\Http\JsonResponse | PageResource
    {
        $page = Page
            ::getById($id)
            ->with('category')
            ->with('pageRevisions')
            ->first();
        if (!$page) {
            return sendErrorResponse('Page # ' . $id . ' not found', HTTP_RESPONSE_NOT_FOUND);
        }
        return new PageResource($page);
    }

    /**
     * Update existing Page model in db.
     *
     * @param \Illuminate\Http\Request $request - data to update
     * @param id $id - Page model Id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(
        array $requestData,
        UploadedFile|string $pageUploadedImageFile = null,
        UploadedFile|string $pageUploadedDocumentFile = null,
        int $id,
        bool $makeValidation = false
    ): \Illuminate\Http\JsonResponse | \Illuminate\Support\MessageBag {

        $loggedUser = Auth::user();
        $page = Page::find($id);
        $rulesPageUpdate = new RulesPageUpdate(actionLabel: "update", id: $id, page : $page, loggedUser: $loggedUser);
        $retResults = $rulesPageUpdate->check( rulesToValidate:
            [
                UpdatePageRules::UPR_PAGE_NOT_FOUND_BY_ID,
                UpdatePageRules::UPR_LOGGED_USER_HAS_NOT_ACTIVE_STATUS_FOR_ACTION,
                UpdatePageRules::UPR_LOGGED_USER_IS_NOT_CREATOR_OF_PAGE,
                UpdatePageRules::ONLY_UNPUBLISHED_PAGE_UPR_CAN_UPDATED
            ],
            checkActiveUser: $this->checkActiveUser
        );
        if(!$retResults['result']) {
            return response()->json($retResults['message'], $retResults['returnCode']);
        }

        DB::beginTransaction();
        try {
            $page->title           = $requestData['title'];
            $page->content         = $requestData['content'];
            $page->content_shortly = $requestData['content_shortly'];
            $page->is_homepage      = ! empty($requestData['is_homepage']);
            $page->published        = ! empty($requestData['published']);
            $page->meta_description = $requestData['meta_description'] ?? '';
            $page->meta_keywords    = $requestData['meta_keywords'] ?? '';
            if (gettype($page->meta_keywords) == 'string') {
                $page->meta_keywords = pregSplit(preg_quote('/;/'), $page->meta_keywords);
            }
            $page->updated_at = Carbon::now(config('app.timezone'));
            $page->save();

            $this->savePageCategories(page: $page, pageCategories: $requestData['page_categories'] ?? '', addPage:
                false);
            $newPageRevision                   = new PageRevision();
            $newPageRevision->page_id          = $page->id;
            $newPageRevision->author_id        = $loggedUser->id;
            $newPageRevision->revisions_number = PageRevision
                                                     ::getByPageId($page->id)
                                                     ->max('revisions_number') + 1;
            $newPageRevision->title            = $page->title;
            $newPageRevision->content          = $page->content;
            $newPageRevision->content_shortly  = $page->content_shortly;
            $newPageRevision->is_homepage      = ! empty($page->is_homepage);
            $newPageRevision->published        = ! empty($page->published);
            $newPageRevision->meta_description = $page->meta_description;
            $newPageRevision->meta_keywords    = $page->meta_keywords;
            $newPageRevision->save();

            $pageImageUpload = new ModelImageUpload($page, 'LocalMediaLibrary');
            if(!empty($pageUploadedImageFile)) {
                $pageUploadedImageResource = $pageImageUpload->uploadFile(
                    fileToUpload: $pageUploadedImageFile,
                    requestName: 'image',
                    fileType: 'cover_image',
                    deleteExitingMediaFiles: true
                );
            }
            if(!empty($pageUploadedDocumentFile)) {
                $pageUploadedDocumentResource = $pageImageUpload->uploadFile(
                    fileToUpload: $pageUploadedDocumentFile,
                    requestName: 'document',
                    fileType: 'document',
                    deleteExitingMediaFiles: true
                );
            }

            $page = Page
                ::getById($page->id)
                ->with('category')
                ->with('pageRevisions')
                ->first();
            DB::commit();

            return response()->json(['page' => (new PageResource($page))],
                HTTP_RESPONSE_OK_RESOURCE_UPDATED); // 201

        } catch (\Exception $e) {
            DB::rollback();
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        } catch (\Error $e) {
            DB::rollback();
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }


    }

    /**
     * Publish existing Page model in db.
     *
     * @param id $id
     *
     * @return \Illuminate\Http\Response
     */
    public function publish(int $id)
    {
        $loggedUser = Auth::user();
        $page = Page::find($id);

        $rulesPageUpdate = new RulesPageUpdate(actionLabel: "publish", id: $id, page : $page, loggedUser: $loggedUser);
        $retResults = $rulesPageUpdate->check( rulesToValidate:
            [
                UpdatePageRules::UPR_PAGE_NOT_FOUND_BY_ID,
                UpdatePageRules::UPR_LOGGED_USER_HAS_NOT_ACTIVE_STATUS_FOR_ACTION,
                UpdatePageRules::UPR_LOGGED_USER_IS_NOT_ADMIN_OR_MANAGER_FOR_ACTION,
                UpdatePageRules::UPR_PAGE_IS_ALREADY_PUBLISHED, //
            ],
            checkActiveUser: $this->checkActiveUser
        );
        if(!$retResults['result']) {
            return response()->json($retResults['message'], $retResults['returnCode']);
        }

        DB::beginTransaction();
        try {
            $page->published  = true;
            $page->updated_at = Carbon::now(config('app.timezone'));

            if ($page->save()) {
                DB::commit();

                return response()->json(['page' => (new PageResource($page))],
                    HTTP_RESPONSE_OK_RESOURCE_UPDATED);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        } catch (\Error $e) {
            DB::rollback();

            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Unpublish existing Page model in db.
     *
     * @param id $id
     *
     * @return \Illuminate\Http\Response
     */
    public function unpublish($id)
    {
        $loggedUser = Auth::user();
        $page = Page::find($id);
        $rulesPageUpdate = new RulesPageUpdate(actionLabel: "publish", id: $id, page : $page, loggedUser: $loggedUser);
        $retResults = $rulesPageUpdate->check( rulesToValidate:
            [
                UpdatePageRules::UPR_PAGE_NOT_FOUND_BY_ID,  //
                UpdatePageRules::UPR_LOGGED_USER_HAS_NOT_ACTIVE_STATUS_FOR_ACTION,
                UpdatePageRules::UPR_LOGGED_USER_IS_NOT_ADMIN_OR_MANAGER_FOR_ACTION, //
                UpdatePageRules::UPR_PAGE_IS_ALREADY_UNPUBLISHED, //
            ],
            checkActiveUser: $this->checkActiveUser
        );
        if(!$retResults['result']) {
            return response()->json($retResults['message'], $retResults['returnCode']);
        }

        DB::beginTransaction();
        try {
            $page->published  = false;
            $page->updated_at = Carbon::now(config('app.timezone'));

            if ($page->save()) {
                DB::commit();

                return response()->json(['page' => (new PageResource($page))],
                    HTTP_RESPONSE_OK_RESOURCE_UPDATED);
            }
        } catch (\Exception $e) {
            DB::rollback();

            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        } catch (\Error $e) {
            DB::rollback();
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Remove the specified Page model by page id from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response | \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $loggedUser = Auth::user();
        if($this->checkActiveUser and $loggedUser->status != 'A') {
            return response()->json('Your account is not active to run "destroy" action', HTTP_RESPONSE_UNAUTHORIZED);
        }
        $page = Page::find($id);
        $rulesPageUpdate = new RulesPageUpdate(actionLabel: "destroy", id: $id, page : $page, loggedUser: $loggedUser);
        $retResults = $rulesPageUpdate->check( rulesToValidate:
            [
                UpdatePageRules::UPR_PAGE_NOT_FOUND_BY_ID,  //
                UpdatePageRules::UPR_LOGGED_USER_HAS_NOT_ACTIVE_STATUS_FOR_ACTION,
                UpdatePageRules::UPR_LOGGED_USER_IS_NOT_ADMIN_OR_MANAGER_FOR_ACTION,
            ],
            checkActiveUser: $this->checkActiveUser
        );
        if(!$retResults['result']) {
            return response()->json($retResults['message'], $retResults['returnCode']);
        }

        DB::beginTransaction();
        try {
            $page->delete();
            DB::commit();
            return response()->noContent();
        } catch (\Exception $e) {
            DB::rollback();
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        } catch (\Error $e) {
            DB::rollback();
            return sendErrorResponse($e->getMessage(), HTTP_RESPONSE_INTERNAL_SERVER_ERROR);
        }
    }

    private function savePageCategories(Page $page, string $pageCategories, bool $addPage = false)
    {
        if ( ! $addPage) {
            foreach ($page->pageCategories as $nextPageCategory) {
                $nextPageCategory->delete();
            }
        }

        $pageCategoriesArray = pregSplit(preg_quote('/;/'), $pageCategories);
        foreach ($pageCategoriesArray as $pageCategoryId) {
            $pageCategory              = new PageCategory();
            $pageCategory->category_id = $pageCategoryId;
            $pageCategory->page_id     = $page->id;
            $pageCategory->save();
        }
    }

}

